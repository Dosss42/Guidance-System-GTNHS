<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Services\BackupService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A restore replaces a real database file, so this test uses its own
 * temporary SQLite file instead of the shared in-memory test database.
 */
class BackupRestoreTest extends TestCase
{
    private string $databasePath;

    private string $backupDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $workDirectory = storage_path('framework/testing');
        File::ensureDirectoryExists($workDirectory);

        $this->databasePath = $workDirectory.'/restore-'.uniqid().'.sqlite';
        $this->backupDirectory = $workDirectory.'/backups-'.uniqid();
        File::put($this->databasePath, '');

        config([
            'database.connections.sqlite.database' => $this->databasePath,
            'backup.path' => $this->backupDirectory,
            'backup.password' => 'test-backup-password-1234',
        ]);

        app('db')->purge('sqlite');
        $this->artisan('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        app('db')->disconnect('sqlite');
        File::delete($this->databasePath);
        File::deleteDirectory($this->backupDirectory);

        parent::tearDown();
    }

    public function test_a_restore_brings_back_deleted_records_and_keeps_a_safety_copy(): void
    {
        $student = Student::factory()->create();
        $service = app(BackupService::class);
        $backup = $service->create();

        Student::query()->delete();
        $this->assertSame(0, Student::count());

        $this->artisan('guidance:restore', ['name' => $backup['name'], '--force' => true])->assertSuccessful();

        $this->assertTrue(Student::sole()->is($student));
        $this->assertTrue($service->list()->contains(fn (array $item): bool => str_ends_with($item['name'], '-pre-restore.zip')));
    }

    public function test_a_restore_refuses_a_backup_that_fails_verification(): void
    {
        $service = app(BackupService::class);
        $backup = $service->create();
        File::put($this->backupDirectory.'/'.$backup['name'], 'corrupted');

        $this->artisan('guidance:restore', ['name' => $backup['name'], '--force' => true])->assertFailed();
    }
}
