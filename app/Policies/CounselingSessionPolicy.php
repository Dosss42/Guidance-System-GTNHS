<?php

namespace App\Policies;

use App\Models\CounselingSession;
use App\Models\User;

/**
 * Shared counselor access; see CounselorRecordPolicy. Reading notes is a
 * separate ability so every unseal can be checked and logged on its own.
 */
class CounselingSessionPolicy extends CounselorRecordPolicy
{
    public function viewNotes(User $user, CounselingSession $session): bool
    {
        return $user->isCounselor();
    }
}
