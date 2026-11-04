<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Proposed counseling session types, pending Guidance Department approval.
 */
enum SessionType: string
{
    use HasOptions;

    case Individual = 'individual';
    case Group = 'group';
    case WithParent = 'with_parent';
    case FollowUp = 'follow_up';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual session',
            self::Group => 'Group session',
            self::WithParent => 'Session with parent / guardian',
            self::FollowUp => 'Follow-up session',
        };
    }
}
