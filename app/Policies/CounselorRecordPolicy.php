<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared access rules for guidance records: every active Guidance Counselor
 * can view, create and update them; the System Administrator has no routine
 * access; nothing is deleted through the application.
 */
abstract class CounselorRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCounselor();
    }

    public function view(User $user, Model $record): bool
    {
        return $user->isCounselor();
    }

    public function create(User $user): bool
    {
        return $user->isCounselor();
    }

    public function update(User $user, Model $record): bool
    {
        return $user->isCounselor();
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }
}
