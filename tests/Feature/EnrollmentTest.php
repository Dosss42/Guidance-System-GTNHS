<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private User $counselor;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counselor = User::factory()->counselor()->create();
        $this->student = Student::factory()->create();
    }

    public function test_a_counselor_adds_an_enrollment_for_a_new_school_year(): void
    {
        $year = SchoolYear::factory()->create();
        $section = Section::factory()->create();

        $this->actingAs($this->counselor)
            ->post(route('counselor.students.enrollments.store', $this->student), [
                'school_year_id' => $year->id,
                'section_id' => $section->id,
                'status' => 'enrolled',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $this->student->id,
            'school_year_id' => $year->id,
            'created_by' => $this->counselor->id,
        ]);
    }

    public function test_a_student_has_at_most_one_enrollment_per_school_year(): void
    {
        $enrollment = StudentEnrollment::factory()->for($this->student)->create();

        $this->actingAs($this->counselor)
            ->post(route('counselor.students.enrollments.store', $this->student), [
                'school_year_id' => $enrollment->school_year_id,
                'section_id' => $enrollment->section_id,
                'status' => 'enrolled',
            ])
            ->assertSessionHasErrors('school_year_id');

        $this->assertSame(1, StudentEnrollment::count());
    }

    public function test_correcting_an_enrollment_keeps_its_school_year(): void
    {
        $enrollment = StudentEnrollment::factory()->for($this->student)->create();
        $otherSection = Section::factory()->create();
        $otherYear = SchoolYear::factory()->create();

        $this->actingAs($this->counselor)
            ->patch(route('counselor.students.enrollments.update', [$this->student, $enrollment]), [
                'section_id' => $otherSection->id,
                'status' => 'transferred_out',
                'school_year_id' => $otherYear->id,
            ])
            ->assertRedirect();

        $enrollment->refresh();
        $this->assertSame($otherSection->id, $enrollment->section_id);
        $this->assertSame(EnrollmentStatus::TransferredOut, $enrollment->status);
        $this->assertNotSame($otherYear->id, $enrollment->school_year_id);
    }

    public function test_an_enrollment_can_only_be_edited_through_its_own_student(): void
    {
        $someoneElse = Student::factory()->create();
        $enrollment = StudentEnrollment::factory()->for($someoneElse)->create();

        $this->actingAs($this->counselor)
            ->patch(route('counselor.students.enrollments.update', [$this->student, $enrollment]), [
                'section_id' => $enrollment->section_id,
                'status' => 'dropped',
            ])
            ->assertNotFound();

        $this->assertSame(EnrollmentStatus::Enrolled, $enrollment->fresh()->status);
    }

    public function test_the_administrator_cannot_change_enrollments(): void
    {
        $enrollment = StudentEnrollment::factory()->for($this->student)->create();

        $this->actingAs(User::factory()->administrator()->create())
            ->patch(route('counselor.students.enrollments.update', [$this->student, $enrollment]), [
                'section_id' => $enrollment->section_id,
                'status' => 'dropped',
            ])
            ->assertForbidden();
    }
}
