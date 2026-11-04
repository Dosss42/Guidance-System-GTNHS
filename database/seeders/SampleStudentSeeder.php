<?php

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Local-only sample school years, sections and synthetic students.
 * None of these names or LRNs belong to real GTNHS students.
 */
class SampleStudentSeeder extends Seeder
{
    /**
     * Section names per grade. Sample names; replace with the school's real list.
     *
     * @var array<int, list<string>>
     */
    private const SECTIONS = [
        7 => ['Mabini', 'Rizal'],
        8 => ['Bonifacio', 'Luna'],
        9 => ['Dagohoy', 'Silang'],
        10 => ['Sampaguita', 'Ilang-Ilang'],
        11 => ['STEM-A', 'HUMSS-B'],
        12 => ['STEM-A', 'ABM-A'],
    ];

    public function run(): void
    {
        $years = collect([2024, 2025, 2026])->map(fn (int $start) => SchoolYear::factory()->startingIn($start)->create());
        $years->last()->markAsCurrent();

        $sections = collect(self::SECTIONS)->flatMap(fn (array $names, int $grade): Collection => collect($names)
            ->map(fn (string $name) => Section::create(['grade_level' => $grade, 'name' => $name])));

        Student::factory()->count(24)->create()->each(function (Student $student, int $index) use ($years, $sections): void {
            $startingGrade = 7 + ($index % 4);

            foreach ($years as $offset => $year) {
                $section = $sections->where('grade_level', $startingGrade + $offset)->random();

                $student->enrollments()->create([
                    'school_year_id' => $year->id,
                    'section_id' => $section->id,
                    'status' => $index === 5 && $offset === 2 ? EnrollmentStatus::TransferredOut : EnrollmentStatus::Enrolled,
                ]);
            }
        });
    }
}
