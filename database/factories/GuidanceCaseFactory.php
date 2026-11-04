<?php

namespace Database\Factories;

use App\Enums\CaseStatus;
use App\Enums\ConcernCategory;
use App\Models\GuidanceCase;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuidanceCase>
 */
class GuidanceCaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'category' => fake()->randomElement(ConcernCategory::cases()),
            'status' => CaseStatus::Open,
            'opened_on' => fake()->dateTimeBetween('-2 months', 'now'),
            'summary' => 'Sample case summary for testing only.',
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CaseStatus::Closed,
            'closed_on' => today(),
            'closure_summary' => 'Sample closure summary.',
        ]);
    }
}
