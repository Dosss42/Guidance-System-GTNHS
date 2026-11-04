<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Services\AuditLogger;
use App\Services\BackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('guidance:restore {name : Archive file name to restore} {--force : Skip the confirmation question}')]
#[Description('Replace the database and documents with a verified backup (a pre-restore backup is made first)')]
class RestoreBackupCommand extends Command
{
    public function handle(BackupService $backups, AuditLogger $audit): int
    {
        $name = (string) $this->argument('name');

        $this->components->warn('Restoring replaces every record in the database with the backup\'s copy.');
        $this->line('  Put the application in maintenance mode first (php artisan down), and tell the counselors.');

        if (! $this->option('force') && ! $this->confirm("Restore {$name} now?")) {
            $this->components->info('Nothing was changed.');

            return self::FAILURE;
        }

        try {
            $safetyCopy = $backups->restore($name);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $audit->record(AuditAction::BackupRestored, 'backup', description: "Restored {$name}; previous state saved as {$safetyCopy}");
        $this->components->info("Restored {$name}. The previous state was saved as {$safetyCopy}.");
        $this->line('  Run php artisan up when you have checked the application.');

        return self::SUCCESS;
    }
}
