<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the administrator overview. It holds account and system
     * information only, never guidance records.
     */
    public function __invoke(): Response
    {
        $activeByRole = User::active()
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->selectRaw('roles.slug as slug, count(*) as total')
            ->groupBy('roles.slug')
            ->pluck('total', 'slug');

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'activeCounselors' => (int) ($activeByRole[RoleSlug::Counselor->value] ?? 0),
                'activeAdministrators' => (int) ($activeByRole[RoleSlug::Administrator->value] ?? 0),
                'deactivatedAccounts' => User::where('is_active', false)->count(),
                'failedSignIns' => AuditLog::where('action', AuditAction::LoginFailed)
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
            ],
            'recentEvents' => AuditLog::with('user:id,name,email')
                ->latest('created_at')
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (AuditLog $log): array => AuditLogController::present($log)),
        ]);
    }
}
