<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the two approved roles. Safe to run more than once.
     */
    public function run(): void
    {
        foreach (RoleSlug::cases() as $slug) {
            Role::updateOrCreate(['slug' => $slug], ['name' => $slug->label()]);
        }
    }
}
