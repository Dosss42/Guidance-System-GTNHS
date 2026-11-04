<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Proposed referral and case categories, pending Guidance Department approval.
 * A category describes the area of concern; it is never a diagnosis.
 */
enum ConcernCategory: string
{
    use HasOptions;

    case Academic = 'academic';
    case Attendance = 'attendance';
    case Behavior = 'behavior';
    case PersonalSocial = 'personal_social';
    case Career = 'career';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Academic => 'Academic concern',
            self::Attendance => 'Attendance',
            self::Behavior => 'Behavior',
            self::PersonalSocial => 'Personal / social',
            self::Career => 'Career guidance',
            self::Other => 'Other',
        };
    }
}
