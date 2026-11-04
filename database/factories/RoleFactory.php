<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Prefer Role::forSlug() in tests: the two roles are fixed and slugs are unique.
 *
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => RoleSlug::Counselor,
            'name' => RoleSlug::Counselor->label(),
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn (array $attributes): array => [
            'slug' => RoleSlug::Administrator,
            'name' => RoleSlug::Administrator->label(),
        ]);
    }
}
