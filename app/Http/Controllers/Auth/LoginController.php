<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Show the sign-in page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/login');
    }

    /**
     * Sign the user in and send them to their role's dashboard.
     */
    public function store(LoginRequest $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->authenticate($audit);

        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        $audit->record(AuditAction::Login, 'auth', actor: $user);

        return redirect()->route($user->dashboardRoute());
    }

    /**
     * Sign the user out and end the session.
     */
    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $audit->record(AuditAction::Logout, 'auth');

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
