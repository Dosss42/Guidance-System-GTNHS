<?php

namespace App\Enums;

/**
 * The two approved system roles. There are no teacher, student or parent roles.
 */
enum RoleSlug: string
{
    case Administrator = 'administrator';
    case Counselor = 'guidance_counselor';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'System Administrator',
            self::Counselor => 'Guidance Counselor',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Administrator => 'admin.dashboard',
            self::Counselor => 'counselor.dashboard',
        };
    }
}
