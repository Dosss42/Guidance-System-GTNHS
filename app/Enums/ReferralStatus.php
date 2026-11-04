<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ReferralStatus: string
{
    use HasOptions;

    case Received = 'received';
    case UnderReview = 'under_review';
    case ForFollowUp = 'for_follow_up';
    case ReferredOut = 'referred_out';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Received',
            self::UnderReview => 'Under review',
            self::ForFollowUp => 'For follow-up',
            self::ReferredOut => 'Referred to other support',
            self::Closed => 'Closed',
        };
    }

    /**
     * Referrals still waiting for a counselor to look at them.
     *
     * @return list<self>
     */
    public static function awaitingReview(): array
    {
        return [self::Received];
    }
}
