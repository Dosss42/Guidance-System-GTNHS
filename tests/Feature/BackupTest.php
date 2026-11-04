<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\CounselingSession;
use App\Models\Document;
use App\Models\Student;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

/**
 * Uses DatabaseMigrations rather than RefreshDatabase: SQLite cannot
 * snapshot (VACUUM INTO) from inside the transaction RefreshDatabase opens.
 */
class BackupTest extends TestCase
{
    use DatabaseMigrations;

    private string $backupDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->backupDirectory = storage_path('framework/testing/backups-'.uniqid());
        config(['backup.path' => $this->backupDirectory, 'backup.password' => 'test-backup-password-1234', 'backup.keep' => 3]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupDirectory);

        parent::tearDown();
    }

    public function test_a_backup_is_encrypted_and_contains_the_database_and_documents(): void
    {
        $document = Document::factory()->create();
        Storage::disk('local')->put($document->storage_path, '%PDF-1.4 sample');

        $backup = app(BackupService::class)->create();

        $zip = new ZipArchive;
        $zip->open($this->backupDirectory.'/'.$backup['name']);
        $this->assertNotFalse($zip->locateName('database/database.sqlite'));
        $this->assertNotFalse($zip->locateName('files/'.$document->storage_path));
        $this->assertFalse($zip->getFromName('manifest.json'), 'Entries must not be readable without the password.');

        $zip->setPassword('wrong-password-000000');
        $this->assertFalse($zip->getFromName('manifest.json'));

        $zip->setPassword('test-backup-password-1234');
        $manifest = json_decode($zip->getFromName('manifest.json'), true);
        $zip->close();

        $this->assertSame(1, $manifest['table_counts']['documents']);
        $this->assertArrayHasKey('files/'.$document->storage_path, $manifest['entries']);
    }

    public function test_session_notes_stay_encrypted_inside_the_backup_database(): void
    {
        CounselingSession::factory()->create(['confidential_notes' => 'Synthetic secret phrase']);

        $backup = app(BackupService::class)->create();

        $zip = new ZipArchive;
        $zip->open($this->backupDirectory.'/'.$backup['name']);
        $zip->setPassword('test-backup-password-1234');
        $database = (string) $zip->getFromName('database/database.sqlite');
        $zip->close();

        $this->assertNotSame('', $database);
        $this->assertStringNotContainsString('Synthetic secret phrase', $database);
    }

    public function test_a_fresh_backup_verifies_and_a_tampered_one_does_not(): void
    {
        Student::factory()->count(2)->create();
        $service = app(BackupService::class);
        $backup = $service->create();

        $this->assertTrue($service->verify($backup['name'])['ok']);

        $zip = new ZipArchive;
        $zip->open($this->backupDirectory.'/'.$backup['name']);
        $zip->addFromString('database/database.sqlite', 'not a database');
        $zip->setEncryptionName('database/database.sqlite', ZipArchive::EM_AES_256, 'test-backup-password-1234');
        $zip->close();

        $result = $service->verify($backup['name']);
        $this->assertFalse($result['ok']);
        $this->assertContains('Checksum mismatch: database/database.sqlite', $result['problems']);
    }

    public function test_backups_refuse_to_run_without_a_strong_password(): void
    {
        config(['backup.password' => 'short']);

        $this->artisan('guidance:backup')->assertFailed();

        $this->assertSame([], File::isDirectory($this->backupDirectory) ? File::files($this->backupDirectory) : []);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::BackupFailed->value]);
    }

    public function test_the_backup_command_creates_and_verifies_an_archive(): void
    {
        $this->artisan('guidance:backup --verify')->assertSuccessful();

        $this->assertCount(1, app(BackupService::class)->list());
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::BackupCreated->value]);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::BackupVerified->value]);
    }

    public function test_only_the_newest_backups_are_kept(): void
    {
        $service = app(BackupService::class);

        foreach (['20260101-010101', '20260102-010101', '20260103-010101', '20260104-010101'] as $stamp) {
            File::ensureDirectoryExists($this->backupDirectory);
            File::put("{$this->backupDirectory}/gtnhs-guidance-backup-{$stamp}.zip", 'x');
        }

        $this->assertSame(1, $service->prune());
        $this->assertSame(
            ['gtnhs-guidance-backup-20260104-010101.zip', 'gtnhs-guidance-backup-20260103-010101.zip', 'gtnhs-guidance-backup-20260102-010101.zip'],
            $service->list()->pluck('name')->all(),
        );
    }

    public function test_archive_names_cannot_escape_the_backup_folder(): void
    {
        $this->expectExceptionMessage('That is not a backup archive name.');

        app(BackupService::class)->pathFor('../../.env');
    }

    public function test_the_administrator_runs_and_checks_backups_from_the_admin_page(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->post(route('admin.backups.store'))->assertRedirect()->assertSessionHas('status');

        $this->actingAs($administrator)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/backups/index')
                ->where('passwordConfigured', true)
                ->has('backups', 1)
                ->where('backups.0.verification.ok', true));

        $this->assertSame($administrator->id, AuditLog::where('action', AuditAction::BackupCreated)->sole()->user_id);
    }

    public function test_counselors_cannot_reach_backups(): void
    {
        $this->actingAs(User::factory()->counselor()->create());

        $this->get(route('admin.backups.index'))->assertForbidden();
        $this->post(route('admin.backups.store'))->assertForbidden();
    }
}
