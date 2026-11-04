<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Synthetic students only. Never seed real student information.
 *
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lrn' => fake()->unique()->numerify('9##########'.'#'),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional(0.8)->lastName(),
            'last_name' => fake()->lastName(),
            'suffix' => null,
            'birth_date' => fake()->optional(0.7)->dateTimeBetween('-19 years', '-12 years'),
        ];
    }
}
