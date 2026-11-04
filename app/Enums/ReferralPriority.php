<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Proposed priority levels, if the department approves using them.
 */
enum ReferralPriority: string
{
    use HasOptions;

    case Routine = 'routine';
    case ThisWeek = 'this_week';
    case Today = 'today';

    public function label(): string
    {
        return match ($this) {
            self::Routine => 'Routine',
            self::ThisWeek => 'This week',
            self::Today => 'Today',
        };
    }
}
