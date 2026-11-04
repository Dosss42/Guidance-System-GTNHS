<?php

namespace Database\Factories;

use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grade_level' => fake()->randomElement(Section::GRADE_LEVELS),
            'name' => fake()->unique()->lexify('Section ????'),
        ];
    }

    public function grade(int $gradeLevel): static
    {
        return $this->state(fn (array $attributes): array => [
            'grade_level' => $gradeLevel,
        ]);
    }
}
