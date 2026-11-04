<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Filters the administrator can choose. Each maps to a fixed list of actions.
     *
     * @var list<string>
     */
    private const FILTERS = ['all', 'sign-in', 'accounts', 'records', 'files', 'system'];

    /**
     * Show the read-only technical audit log.
     */
    public function __invoke(Request $request): Response
    {
        $filter = in_array($request->query('event'), self::FILTERS, true) ? $request->query('event') : 'all';

        $logs = AuditLog::with('user:id,name,email')
            ->when($filter === 'sign-in', fn ($query) => $query->whereIn('action', AuditAction::authenticationEvents()))
            ->when($filter === 'accounts', fn ($query) => $query->whereIn('action', AuditAction::accountEvents()))
            ->when($filter === 'records', fn ($query) => $query->whereIn('action', AuditAction::recordEvents()))
            ->when($filter === 'files', fn ($query) => $query->whereIn('action', AuditAction::fileEvents()))
            ->when($filter === 'system', fn ($query) => $query->whereIn('action', AuditAction::systemEvents()))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => self::present($log));

        return Inertia::render('admin/audit-logs/index', [
            'logs' => $logs,
            'filter' => $filter,
        ]);
    }

    /**
     * The fields of an audit entry that are safe to show an administrator.
     *
     * @return array{id: int, at: string, user: ?string, action: string, actionLabel: string, module: string, record: ?string, description: ?string, ip: ?string}
     */
    public static function present(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'at' => $log->created_at->toIso8601String(),
            'user' => $log->user?->email,
            'action' => $log->action->value,
            'actionLabel' => $log->action->label(),
            'module' => $log->module,
            'record' => $log->record_type === null ? null : "{$log->record_type} #{$log->record_id}",
            'description' => $log->description,
            'ip' => $log->ip_address,
        ];
    }
}
