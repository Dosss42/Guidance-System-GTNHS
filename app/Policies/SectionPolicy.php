<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

/**
 * Sections are reference data maintained by the counselors.
 */
class SectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCounselor();
    }

    public function create(User $user): bool
    {
        return $user->isCounselor();
    }

    public function update(User $user, Section $section): bool
    {
        return $user->isCounselor();
    }

    public function delete(User $user, Section $section): bool
    {
        return false;
    }
}
