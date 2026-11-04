<?php

namespace App\Policies;

use App\Models\User;

/**
 * Only the System Administrator manages accounts. Accounts are never
 * deleted; they are deactivated so their signed history stays intact.
 */
class UserPolicy
{
    /**
     * Determine whether the user can view the account list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can create accounts.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update an account.
     */
    public function update(User $user, User $account): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can activate or deactivate an account.
     * Administrators cannot deactivate themselves.
     */
    public function changeStatus(User $user, User $account): bool
    {
        return $user->isAdministrator() && ! $user->is($account);
    }

    /**
     * Accounts are never deleted through the application.
     */
    public function delete(User $user, User $account): bool
    {
        return false;
    }
}
