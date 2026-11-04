<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * How the office reached a parent or guardian.
 */
enum ContactMethod: string
{
    use HasOptions;

    case Phone = 'phone';
    case InPerson = 'in_person';
    case Message = 'message';
    case Letter = 'letter';
    case HomeVisit = 'home_visit';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Phone call',
            self::InPerson => 'In person',
            self::Message => 'Text or chat message',
            self::Letter => 'Letter',
            self::HomeVisit => 'Home visit',
        };
    }
}
