<?php

namespace App\Enums;

/**
 * Proposed enrollment statuses, pending Guidance Department confirmation.
 */
enum EnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case TransferredOut = 'transferred_out';
    case Dropped = 'dropped';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Enrolled => 'Enrolled',
            self::TransferredOut => 'Transferred out',
            self::Dropped => 'Dropped',
            self::Completed => 'Completed',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status): array => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
