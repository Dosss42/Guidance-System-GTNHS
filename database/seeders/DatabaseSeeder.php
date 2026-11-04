<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Roles are always seeded. Sample accounts are created only in the local
     * environment and use synthetic names — never real staff or students.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        if (! app()->environment('local')) {
            return;
        }

        $samplePassword = 'Sample-Pass-2026';

        User::factory()->administrator()->create([
            'name' => 'Ricardo Torres',
            'email' => 'admin@gtnhs.test',
            'password' => $samplePassword,
        ]);

        User::factory()->counselor()->create([
            'name' => 'Marites Santos',
            'email' => 'm.santos@gtnhs.test',
            'password' => $samplePassword,
        ]);

        User::factory()->counselor()->create([
            'name' => 'Jonathan Ramos',
            'email' => 'j.ramos@gtnhs.test',
            'password' => $samplePassword,
        ]);

        $this->call(SampleStudentSeeder::class);
        $this->call(SampleCaseworkSeeder::class);

        $this->command?->info("Sample accounts created. Password for all: {$samplePassword}");
    }
}
