<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Proposed document types, pending Guidance Department approval.
 */
enum DocumentCategory: string
{
    use HasOptions;

    case ReferralSlip = 'referral_slip';
    case ConsentForm = 'consent_form';
    case GradeReport = 'grade_report';
    case Letter = 'letter';
    case AssessmentResult = 'assessment_result';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ReferralSlip => 'Referral slip',
            self::ConsentForm => 'Consent form',
            self::GradeReport => 'Grade report',
            self::Letter => 'Letter',
            self::AssessmentResult => 'Assessment result',
            self::Other => 'Other',
        };
    }
}
