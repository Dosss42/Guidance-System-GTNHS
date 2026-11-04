<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * How a referral reached the office through the school's existing process.
 */
enum ReferralChannel: string
{
    use HasOptions;

    case PaperSlip = 'paper_slip';
    case InPerson = 'in_person';
    case Message = 'message';

    public function label(): string
    {
        return match ($this) {
            self::PaperSlip => 'Paper slip',
            self::InPerson => 'In person',
            self::Message => 'Message or email',
        };
    }
}
