<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Creates, verifies and restores encrypted backups of the database and the
 * private documents. Every entry in an archive is AES-256 encrypted, and a
 * manifest records each entry's SHA-256 so corruption is detectable.
 */
class BackupService
{
    public const NAME_PATTERN = '/^gtnhs-guidance-backup-\d{8}-\d{6}(-[a-z-]+)?\.zip$/';

    /**
     * Tables whose row counts go in the manifest, so a restore can be sanity-checked.
     *
     * @var list<string>
     */
    private const COUNTED_TABLES = ['users', 'students', 'student_enrollments', 'referrals', 'guidance_cases', 'counseling_sessions', 'follow_ups', 'appointments', 'parent_coordinations', 'documents', 'audit_logs'];

    public function directory(): string
    {
        $directory = (string) config('backup.path');
        File::ensureDirectoryExists($directory, 0700);

        return $directory;
    }

    /**
     * Write a new encrypted backup and prune old ones.
     *
     * @return array{name: string, size: int, entries: int}
     */
    public function create(?User $by = null, string $label = ''): array
    {
        $password = $this->password();
        $name = 'gtnhs-guidance-backup-'.now()->format('Ymd-His').($label !== '' ? '-'.Str::slug($label) : '').'.zip';
        $archivePath = $this->directory().DIRECTORY_SEPARATOR.$name;
        $workDir = $this->temporaryDirectory();

        try {
            $entries = [$this->dumpDatabase($workDir)];

            foreach (Storage::disk(Document::DISK)->allFiles('documents') as $file) {
                $entries[] = ['name' => "files/{$file}", 'path' => Storage::disk(Document::DISK)->path($file)];
            }

            $zip = new ZipArchive;

            if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new RuntimeException("Could not create {$name}.");
            }

            $manifest = [
                'format' => 1,
                'created_at' => now()->toIso8601String(),
                'created_by' => $by?->email ?? 'scheduled task',
                'app_env' => app()->environment(),
                'database_driver' => DB::connection()->getDriverName(),
                'table_counts' => $this->tableCounts(),
                'entries' => [],
            ];

            foreach ($entries as $entry) {
                $zip->addFile($entry['path'], $entry['name']);
                $zip->setEncryptionName($entry['name'], ZipArchive::EM_AES_256, $password);
                $manifest['entries'][$entry['name']] = ['sha256' => hash_file('sha256', $entry['path']), 'bytes' => filesize($entry['path'])];
            }

            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $zip->setEncryptionName('manifest.json', ZipArchive::EM_AES_256, $password);

            if (! $zip->close()) {
                throw new RuntimeException("Could not finish writing {$name}.");
            }
        } catch (Throwable $exception) {
            File::delete($archivePath);

            throw $exception;
        } finally {
            $this->removeWorkDirectory($workDir);
        }

        $this->prune();

        return ['name' => $name, 'size' => filesize($archivePath), 'entries' => count($entries)];
    }

    /**
     * Check that an archive opens with the password, every entry matches its
     * checksum, and the database copy is readable. The result is saved next
     * to the archive so the admin page can show it.
     *
     * @return array{ok: bool, problems: list<string>, verifiedAt: string, manifest: array<string, mixed>|null}
     */
    public function verify(string $name): array
    {
        $zip = $this->open($name);
        $problems = [];
        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);

        if (! is_array($manifest) || ! isset($manifest['entries'])) {
            $zip->close();

            return $this->saveVerification($name, ['ok' => false, 'problems' => ['The manifest could not be read. The password may be wrong or the archive damaged.'], 'manifest' => null]);
        }

        foreach ($manifest['entries'] as $entryName => $expected) {
            $stream = $zip->getStream($entryName);

            if ($stream === false) {
                $problems[] = "Missing entry: {$entryName}";

                continue;
            }

            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            fclose($stream);

            if (! hash_equals($expected['sha256'], hash_final($context))) {
                $problems[] = "Checksum mismatch: {$entryName}";
            }
        }

        $problems = [...$problems, ...$this->checkDatabaseEntry($zip, $manifest)];
        $zip->close();

        return $this->saveVerification($name, ['ok' => $problems === [], 'problems' => $problems, 'manifest' => $manifest]);
    }

    /**
     * Replace the database (and restore documents) from a verified archive.
     * An encrypted "pre-restore" backup of the current state is made first.
     */
    public function restore(string $name, ?User $by = null): string
    {
        $verification = $this->verify($name);

        if (! $verification['ok']) {
            throw new RuntimeException('The backup failed verification: '.implode('; ', $verification['problems']));
        }

        $safetyCopy = $this->create($by, 'pre-restore')['name'];
        $zip = $this->open($name);
        $workDir = $this->temporaryDirectory();
        $driver = $verification['manifest']['database_driver'] ?? null;

        try {
            if ($driver !== DB::connection()->getDriverName()) {
                throw new RuntimeException("This backup is from a {$driver} database; the app is using ".DB::connection()->getDriverName().'.');
            }

            if ($driver === 'sqlite') {
                $target = (string) config('database.connections.sqlite.database');

                if ($target === ':memory:' || $target === '') {
                    throw new RuntimeException('An in-memory database cannot be restored.');
                }

                $zip->extractTo($workDir, 'database/database.sqlite');
                DB::disconnect();
                File::copy($workDir.'/database/database.sqlite', $target);
                DB::reconnect();
            } else {
                $zip->extractTo($workDir, 'database/dump.sql');
                $this->importMysql($workDir.'/database/dump.sql');
            }

            foreach (array_keys($verification['manifest']['entries']) as $entryName) {
                if (str_starts_with($entryName, 'files/documents/')) {
                    Storage::disk(Document::DISK)->put(Str::after($entryName, 'files/'), (string) $zip->getFromName($entryName));
                }
            }
        } finally {
            $zip->close();
            $this->removeWorkDirectory($workDir);
        }

        return $safetyCopy;
    }

    /**
     * Archives, newest first, with their last verification result.
     *
     * @return Collection<int, array{name: string, size: int, createdAt: string, verification: array<string, mixed>|null}>
     */
    public function list(): Collection
    {
        return collect(File::files($this->directory()))
            ->filter(fn ($file): bool => (bool) preg_match(self::NAME_PATTERN, $file->getFilename()))
            ->map(fn ($file): array => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'createdAt' => Carbon::createFromTimestamp($file->getMTime())->toIso8601String(),
                'verification' => $this->readVerification($file->getFilename()),
            ])
            ->sortByDesc('name')
            ->values();
    }

    /**
     * Delete all but the newest `backup.keep` archives.
     */
    public function prune(): int
    {
        $stale = $this->list()->slice(max(1, (int) config('backup.keep')));

        $stale->each(function (array $backup): void {
            File::delete($this->pathFor($backup['name']));
            File::delete($this->pathFor($backup['name']).'.verified.json');
        });

        return $stale->count();
    }

    public function pathFor(string $name): string
    {
        if (! preg_match(self::NAME_PATTERN, $name)) {
            throw new RuntimeException('That is not a backup archive name.');
        }

        return $this->directory().DIRECTORY_SEPARATOR.$name;
    }

    /**
     * @return array{name: string, path: string}
     */
    private function dumpDatabase(string $workDir): array
    {
        $connection = DB::connection();

        if ($connection->getDriverName() === 'sqlite') {
            $path = $workDir.DIRECTORY_SEPARATOR.'database.sqlite';
            $connection->statement('VACUUM INTO ?', [$path]);

            return ['name' => 'database/database.sqlite', 'path' => $path];
        }

        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            $config = $connection->getConfig();
            $path = $workDir.DIRECTORY_SEPARATOR.'dump.sql';

            $result = Process::env(['MYSQL_PWD' => (string) ($config['password'] ?? '')])
                ->timeout(600)
                ->run([
                    config('backup.mysqldump_binary'),
                    '--single-transaction',
                    '--routines',
                    '--no-tablespaces',
                    '--host='.($config['host'] ?? '127.0.0.1'),
                    '--port='.($config['port'] ?? 3306),
                    '--user='.($config['username'] ?? 'root'),
                    '--result-file='.$path,
                    (string) $config['database'],
                ]);

            if ($result->failed()) {
                throw new RuntimeException('mysqldump failed: '.trim($result->errorOutput()));
            }

            return ['name' => 'database/dump.sql', 'path' => $path];
        }

        throw new RuntimeException("Backups are not supported for the {$connection->getDriverName()} driver.");
    }

    private function importMysql(string $dumpPath): void
    {
        $config = DB::connection()->getConfig();

        $result = Process::env(['MYSQL_PWD' => (string) ($config['password'] ?? '')])
            ->timeout(600)
            ->input(fopen($dumpPath, 'r'))
            ->run([
                config('backup.mysql_binary'),
                '--host='.($config['host'] ?? '127.0.0.1'),
                '--port='.($config['port'] ?? 3306),
                '--user='.($config['username'] ?? 'root'),
                (string) $config['database'],
            ]);

        if ($result->failed()) {
            throw new RuntimeException('Restoring the MySQL dump failed: '.trim($result->errorOutput()));
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function checkDatabaseEntry(ZipArchive $zip, array $manifest): array
    {
        if (($manifest['database_driver'] ?? null) !== 'sqlite') {
            $dump = (string) $zip->getFromName('database/dump.sql');

            return str_contains($dump, 'CREATE TABLE `users`') ? [] : ['The database dump does not contain the users table.'];
        }

        $workDir = $this->temporaryDirectory();

        try {
            if (! $zip->extractTo($workDir, 'database/database.sqlite')) {
                return ['The database copy could not be extracted.'];
            }

            $pdo = new PDO('sqlite:'.$workDir.'/database/database.sqlite');
            $statement = $pdo->query('PRAGMA integrity_check');
            $integrity = $statement->fetchColumn();
            $statement->closeCursor();
            $statement = $pdo->query('SELECT count(*) FROM users');
            $userCount = (int) $statement->fetchColumn();
            $statement->closeCursor();

            // Release every handle so Windows lets the temporary copy be deleted.
            $statement = null;
            $pdo = null;

            $problems = $integrity === 'ok' ? [] : ["Database integrity check failed: {$integrity}"];

            if ($userCount !== (int) ($manifest['table_counts']['users'] ?? -1)) {
                $problems[] = 'The database copy has a different number of user accounts than the manifest records.';
            }

            return $problems;
        } catch (Throwable $exception) {
            return ['The database copy could not be opened: '.$exception->getMessage()];
        } finally {
            $statement = null;
            $pdo = null;
            $this->removeWorkDirectory($workDir);
        }
    }

    /**
     * Delete an unencrypted scratch copy. PDO can hold the SQLite file open
     * until garbage collection runs, and Windows refuses to delete an open
     * file, so collect first and retry briefly.
     */
    private function removeWorkDirectory(string $workDir): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            gc_collect_cycles();

            if (File::deleteDirectory($workDir) || ! File::isDirectory($workDir)) {
                return;
            }

            usleep(200_000);
        }

        report(new RuntimeException("Could not remove the backup work folder {$workDir}. Delete it by hand: it holds an unencrypted database copy."));
    }

    /**
     * @return array<string, int>
     */
    private function tableCounts(): array
    {
        return collect(self::COUNTED_TABLES)
            ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])
            ->all();
    }

    private function open(string $name): ZipArchive
    {
        $path = $this->pathFor($name);

        if (! File::exists($path)) {
            throw new RuntimeException("Backup {$name} was not found.");
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException("Backup {$name} could not be opened.");
        }

        $zip->setPassword($this->password());

        return $zip;
    }

    private function password(): string
    {
        $password = (string) config('backup.password');

        if (strlen($password) < 16) {
            throw new RuntimeException('Set BACKUP_PASSWORD in .env (at least 16 characters) before creating or reading backups.');
        }

        return $password;
    }

    /**
     * Scratch space for unencrypted copies while a backup is built or checked.
     * It lives under storage/app, which Git ignores, and is deleted afterwards.
     */
    private function temporaryDirectory(): string
    {
        $root = storage_path('app/backup-work');

        foreach (File::isDirectory($root) ? File::directories($root) : [] as $stale) {
            if (filemtime($stale) < now()->subMinutes(10)->getTimestamp()) {
                File::deleteDirectory($stale);
            }
        }

        $path = $root.DIRECTORY_SEPARATOR.Str::random(16);
        File::ensureDirectoryExists($path, 0700);

        return $path;
    }

    /**
     * @param  array{ok: bool, problems: list<string>, manifest: array<string, mixed>|null}  $result
     * @return array{ok: bool, problems: list<string>, verifiedAt: string, manifest: array<string, mixed>|null}
     */
    private function saveVerification(string $name, array $result): array
    {
        $result['verifiedAt'] = now()->toIso8601String();

        File::put($this->pathFor($name).'.verified.json', json_encode([
            'ok' => $result['ok'],
            'problems' => $result['problems'],
            'verifiedAt' => $result['verifiedAt'],
            'tableCounts' => $result['manifest']['table_counts'] ?? null,
        ]));

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readVerification(string $name): ?array
    {
        $path = $this->pathFor($name).'.verified.json';

        return File::exists($path) ? json_decode(File::get($path), true) : null;
    }
}
