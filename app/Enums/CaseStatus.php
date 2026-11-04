<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CaseStatus: string
{
    use HasOptions;

    case Open = 'open';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::OnHold => 'On hold',
            self::Closed => 'Closed',
        };
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Open, self::InProgress, self::OnHold];
    }
}
