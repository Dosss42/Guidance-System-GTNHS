<?php

namespace App\Policies;

use App\Models\SchoolYear;
use App\Models\User;

/**
 * School years are reference data maintained by the counselors.
 */
class SchoolYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCounselor();
    }

    public function create(User $user): bool
    {
        return $user->isCounselor();
    }

    public function update(User $user, SchoolYear $schoolYear): bool
    {
        return $user->isCounselor();
    }

    public function delete(User $user, SchoolYear $schoolYear): bool
    {
        return false;
    }
}
