<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Services\AuditLogger;
use App\Services\BackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('guidance:backup-verify {name? : Archive file name; the newest one if omitted}')]
#[Description('Check that a backup opens, matches its checksums and contains a readable database')]
class VerifyBackupCommand extends Command
{
    public function handle(BackupService $backups, AuditLogger $audit): int
    {
        $name = $this->argument('name') ?? $backups->list()->first()['name'] ?? null;

        if ($name === null) {
            $this->components->error('There are no backups yet. Run php artisan guidance:backup first.');

            return self::FAILURE;
        }

        $result = $backups->verify($name);

        if (! $result['ok']) {
            $audit->record(AuditAction::BackupFailed, 'backup', description: "{$name} failed verification");
            $this->components->error("{$name} failed verification:");
            $this->components->bulletList($result['problems']);

            return self::FAILURE;
        }

        $audit->record(AuditAction::BackupVerified, 'backup', description: $name);
        $this->components->info("{$name} is intact.");
        $this->table(['Table', 'Rows'], collect($result['manifest']['table_counts'])->map(fn (int $rows, string $table): array => [$table, $rows])->values());

        return self::SUCCESS;
    }
}
