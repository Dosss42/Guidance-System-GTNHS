<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Writes audit log entries. Never pass passwords or counseling note
 * contents in the description — record what happened, not the data.
 */
class AuditLogger
{
    public function __construct(private Request $request) {}

    public function record(
        AuditAction $action,
        string $module,
        ?Model $subject = null,
        ?string $description = null,
        ?User $actor = null,
    ): AuditLog {
        $actor ??= $this->request->user();

        return AuditLog::create([
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'module' => $module,
            'record_type' => $subject?->getTable(),
            'record_id' => $subject?->getKey(),
            'description' => $description === null ? null : Str::limit($description, 250),
            'ip_address' => $this->request->ip(),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 250, ''),
        ]);
    }
}
