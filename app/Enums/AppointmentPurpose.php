<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Proposed appointment purposes, pending Guidance Department approval.
 */
enum AppointmentPurpose: string
{
    use HasOptions;

    case InitialInterview = 'initial_interview';
    case Counseling = 'counseling';
    case FollowUp = 'follow_up';
    case ReferralReview = 'referral_review';
    case CareerPlanning = 'career_planning';
    case ParentConference = 'parent_conference';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::InitialInterview => 'Initial interview',
            self::Counseling => 'Counseling',
            self::FollowUp => 'Follow-up conversation',
            self::ReferralReview => 'Referral review',
            self::CareerPlanning => 'Career planning',
            self::ParentConference => 'Parent conference',
            self::Other => 'Other',
        };
    }
}
