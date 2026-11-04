<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every user's own account page: profile details and password change.
 * Name, email and role are managed by the System Administrator.
 */
class AccountController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('account/index', [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->slug?->label(),
                'lastLoginAt' => $user->last_login_at?->toIso8601String(),
            ],
            'otherSessions' => self::otherSessions($request)->count(),
        ]);
    }

    /**
     * Change the password and sign out every other device using this account.
     */
    public function updatePassword(UpdatePasswordRequest $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['password' => $request->validated('password')])->save();

        $endedSessions = self::otherSessions($request)->delete();
        $request->session()->regenerate();

        $audit->record(AuditAction::PasswordChanged, 'accounts', $user, $endedSessions > 0 ? "Signed out {$endedSessions} other session(s)" : null);

        return back()->with('status', $endedSessions > 0
            ? 'Password changed. Your other signed-in devices have been signed out.'
            : 'Password changed.');
    }

    /**
     * End every database session for a user, e.g. after an administrator
     * resets their password or deactivates them.
     */
    public static function endSessionsFor(User $user): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
    }

    private static function otherSessions(Request $request): Builder
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId());
    }
}
