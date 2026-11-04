<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List every account. Accounts are deactivated, never deleted.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('admin/users/index', [
            'users' => User::with('role')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->slug?->value,
                    'roleLabel' => $user->role?->slug?->label(),
                    'isActive' => $user->is_active,
                    'lastLoginAt' => $user->last_login_at?->toIso8601String(),
                    'isCurrentUser' => $user->is($request->user()),
                ]),
            'roles' => collect(RoleSlug::cases())->map(fn (RoleSlug $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
        ]);
    }

    /**
     * Create an account with an initial password set by the administrator.
     */
    public function store(StoreUserRequest $request, AuditLogger $audit): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role_id' => Role::forSlug(RoleSlug::from($request->validated('role')))->id,
            'is_active' => true,
        ]);

        $audit->record(AuditAction::AccountCreated, 'accounts', $user, "Role: {$user->role->slug->label()}");

        return back()->with('status', "Account created for {$user->name}.");
    }

    /**
     * Update account details. A new password is optional.
     */
    public function update(UpdateUserRequest $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $user->fill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role_id' => Role::forSlug(RoleSlug::from($request->validated('role')))->id,
        ]);

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $changedFields = collect(array_keys($user->getDirty()))
            ->map(fn (string $field): string => $field === 'role_id' ? 'role' : $field)
            ->implode(', ');

        if ($changedFields === '') {
            return back()->with('status', 'No changes to save.');
        }

        $user->save();

        $audit->record(AuditAction::AccountUpdated, 'accounts', $user, "Changed: {$changedFields}");

        return back()->with('status', "Saved changes to {$user->name}'s account.");
    }
}
