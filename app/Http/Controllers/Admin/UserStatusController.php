<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserStatusController extends Controller
{
    /**
     * Activate or deactivate an account. A deactivated user is signed out
     * on their next request and cannot sign in again.
     */
    public function __invoke(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('changeStatus', $user);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $validated['is_active'];

        if ($user->is_active === $isActive) {
            return back();
        }

        $user->update(['is_active' => $isActive]);

        if (! $isActive) {
            AccountController::endSessionsFor($user);
        }

        $audit->record($isActive ? AuditAction::AccountActivated : AuditAction::AccountDeactivated, 'accounts', $user);

        return back()->with('status', $isActive
            ? "{$user->name}'s account is active again."
            : "{$user->name}'s account is deactivated. Their records and signed entries stay as they are.");
    }
}
