<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Services\AuditLogger;
use App\Services\BackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Number;
use Throwable;

#[Signature('guidance:backup {--verify : Verify the archive right after creating it}')]
#[Description('Create an encrypted backup of the database and private documents')]
class BackupCommand extends Command
{
    public function handle(BackupService $backups, AuditLogger $audit): int
    {
        try {
            $backup = $backups->create();
        } catch (Throwable $exception) {
            $audit->record(AuditAction::BackupFailed, 'backup', description: 'Backup failed: '.$exception->getMessage());
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $audit->record(AuditAction::BackupCreated, 'backup', description: "{$backup['name']} · ".Number::fileSize($backup['size']));
        $this->components->info("Created {$backup['name']} (".Number::fileSize($backup['size']).", {$backup['entries']} files).");

        if ($this->option('verify')) {
            return $this->call('guidance:backup-verify', ['name' => $backup['name']]);
        }

        return self::SUCCESS;
    }
}
