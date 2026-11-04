<?php

namespace Database\Factories;

use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<SchoolYear>
 */
class SchoolYearFactory extends Factory
{
    /**
     * Define the model's default state: a June-to-April school year.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2000, 2090);

        return [
            'name' => "{$startYear}–".($startYear + 1),
            'start_date' => Carbon::create($startYear, 6, 15),
            'end_date' => Carbon::create($startYear + 1, 4, 15),
            'is_current' => false,
        ];
    }

    public function startingIn(int $startYear): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => "{$startYear}–".($startYear + 1),
            'start_date' => Carbon::create($startYear, 6, 15),
            'end_date' => Carbon::create($startYear + 1, 4, 15),
        ]);
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_current' => true,
        ]);
    }
}
