<?php

namespace Database\Factories;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use App\Models\GuidanceCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guidance_case_id' => GuidanceCase::factory(),
            'due_on' => fake()->dateTimeBetween('now', '+2 weeks'),
            'purpose' => 'Check-in with adviser',
            'status' => FollowUpStatus::Pending,
        ];
    }

    public function overdue(int $days = 3): static
    {
        return $this->state(fn (array $attributes): array => ['due_on' => today()->subDays($days)]);
    }
}
