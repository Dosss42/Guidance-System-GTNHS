<?php

namespace Database\Factories;

use App\Enums\ConcernCategory;
use App\Enums\ReferralChannel;
use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use App\Models\Referral;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referral>
 */
class ReferralFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'received_on' => fake()->dateTimeBetween('-3 weeks', 'now'),
            'referring_teacher' => 'Ms. '.fake()->lastName(),
            'teacher_role' => 'Adviser',
            'received_via' => fake()->randomElement(ReferralChannel::cases()),
            'category' => fake()->randomElement(ConcernCategory::cases()),
            'priority' => ReferralPriority::Routine,
            'reason' => 'Sample referral reason for testing only.',
            'status' => ReferralStatus::Received,
        ];
    }

    public function status(ReferralStatus $status): static
    {
        return $this->state(fn (array $attributes): array => ['status' => $status]);
    }
}
