<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum FollowUpStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Completed = 'completed';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Rescheduled => 'Rescheduled',
            self::Cancelled => 'Cancelled',
        };
    }
}
