<?php

namespace Database\Factories;

use App\Enums\ContactMethod;
use App\Models\ParentCoordination;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParentCoordination>
 */
class ParentCoordinationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'coordinated_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'contact_name' => fake()->name(),
            'relationship' => fake()->randomElement(['Mother', 'Father', 'Guardian', 'Grandmother']),
            'method' => fake()->randomElement(ContactMethod::cases()),
            'purpose' => 'Sample coordination purpose.',
        ];
    }
}
