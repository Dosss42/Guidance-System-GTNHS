<?php

namespace Database\Factories;

use App\Enums\SessionType;
use App\Models\CounselingSession;
use App\Models\GuidanceCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CounselingSession>
 */
class CounselingSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guidance_case_id' => GuidanceCase::factory(),
            'session_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'session_type' => SessionType::Individual,
            'confidential_notes' => '[Sample session note — synthetic data for testing only.]',
            'next_steps' => 'Sample next step.',
        ];
    }
}
