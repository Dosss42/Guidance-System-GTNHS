<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Backup status for the System Administrator. Archives are encrypted and
 * cannot be downloaded or restored from the browser; restoring is a
 * deliberate command-line step (see guidance:restore).
 */
class BackupController extends Controller
{
    public function index(BackupService $backups): Response
    {
        $passwordConfigured = strlen((string) config('backup.password')) >= 16;

        return Inertia::render('admin/backups/index', [
            'backups' => $passwordConfigured ? $backups->list()->map(fn (array $backup): array => [
                ...$backup,
                'sizeLabel' => Number::fileSize($backup['size'], precision: 1),
            ]) : [],
            'passwordConfigured' => $passwordConfigured,
            'keep' => (int) config('backup.keep'),
            'lastFailure' => AuditLog::where('action', AuditAction::BackupFailed)->latest('created_at')->first()?->only(['description', 'created_at']),
        ]);
    }

    /**
     * Run a backup now, then verify it.
     */
    public function store(Request $request, BackupService $backups, AuditLogger $audit): RedirectResponse
    {
        try {
            $backup = $backups->create($request->user());
            $audit->record(AuditAction::BackupCreated, 'backup', description: "{$backup['name']} · ".Number::fileSize($backup['size']));
        } catch (Throwable $exception) {
            $audit->record(AuditAction::BackupFailed, 'backup', description: 'Backup failed: '.$exception->getMessage());

            return back()->with('status', 'The backup failed: '.$exception->getMessage());
        }

        return $this->verify($backup['name'], $backups, $audit, "Backup {$backup['name']} created");
    }

    /**
     * Check an existing archive.
     */
    public function update(string $name, BackupService $backups, AuditLogger $audit): RedirectResponse
    {
        return $this->verify($name, $backups, $audit, "Backup {$name} checked");
    }

    private function verify(string $name, BackupService $backups, AuditLogger $audit, string $prefix): RedirectResponse
    {
        try {
            $result = $backups->verify($name);
        } catch (Throwable $exception) {
            $audit->record(AuditAction::BackupFailed, 'backup', description: "{$name}: ".$exception->getMessage());

            return back()->with('status', "{$prefix}, but it could not be checked: {$exception->getMessage()}");
        }

        $audit->record($result['ok'] ? AuditAction::BackupVerified : AuditAction::BackupFailed, 'backup', description: $name);

        return back()->with('status', $result['ok']
            ? "{$prefix} and verified: every file matches its checksum and the database opens."
            : "{$prefix}, but verification found problems: ".implode('; ', $result['problems']));
    }
}
