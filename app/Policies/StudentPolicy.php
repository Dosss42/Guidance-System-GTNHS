<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

/**
 * Student records are shared by every active Guidance Counselor.
 * The System Administrator has no routine access. Records are never deleted.
 */
class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCounselor();
    }

    public function view(User $user, Student $student): bool
    {
        return $user->isCounselor();
    }

    public function create(User $user): bool
    {
        return $user->isCounselor();
    }

    public function update(User $user, Student $student): bool
    {
        return $user->isCounselor();
    }

    public function delete(User $user, Student $student): bool
    {
        return false;
    }
}
