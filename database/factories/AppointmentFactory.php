<?php

namespace Database\Factories;

use App\Enums\AppointmentPurpose;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'scheduled_at' => today()->addDays(fake()->numberBetween(1, 10))->setTime(fake()->numberBetween(8, 15), 0),
            'duration_minutes' => 30,
            'purpose' => fake()->randomElement(AppointmentPurpose::cases()),
            'status' => AppointmentStatus::Scheduled,
        ];
    }
}
