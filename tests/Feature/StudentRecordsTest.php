<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentRecordsTest extends TestCase
{
    use RefreshDatabase;

    private User $counselor;

    private SchoolYear $currentYear;

    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counselor = User::factory()->counselor()->create();
        $this->currentYear = SchoolYear::factory()->startingIn(2026)->current()->create();
        $this->section = Section::factory()->grade(10)->create(['name' => 'Sampaguita']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validStudent(array $overrides = []): array
    {
        return [
            'lrn' => '1000 0000 0214',
            'first_name' => '  Andrea ',
            'middle_name' => 'Reyes',
            'last_name' => 'Villanueva',
            'suffix' => '',
            'birth_date' => '2011-03-14',
            ...$overrides,
        ];
    }

    public function test_a_counselor_adds_a_student_with_a_current_enrollment(): void
    {
        $this->actingAs($this->counselor)
            ->post(route('counselor.students.store'), $this->validStudent([
                'school_year_id' => $this->currentYear->id,
                'section_id' => $this->section->id,
                'status' => 'enrolled',
            ]))
            ->assertRedirect();

        $student = Student::sole();
        $this->assertSame('100000000214', $student->lrn);
        $this->assertSame('Andrea', $student->first_name);
        $this->assertNull($student->suffix);
        $this->assertSame($this->counselor->id, $student->created_by);
        $this->assertSame('Villanueva, Andrea R.', $student->filingName());

        $enrollment = StudentEnrollment::sole();
        $this->assertTrue($enrollment->student->is($student));
        $this->assertSame($this->counselor->id, $enrollment->created_by);

        $this->assertSame(2, AuditLog::where('action', AuditAction::RecordCreated)->count());
    }

    public function test_a_student_can_be_saved_without_an_enrollment(): void
    {
        $this->actingAs($this->counselor)
            ->post(route('counselor.students.store'), $this->validStudent())
            ->assertRedirect();

        $this->assertSame(1, Student::count());
        $this->assertSame(0, StudentEnrollment::count());
    }

    public function test_student_input_is_validated(): void
    {
        Student::factory()->create(['lrn' => '100000000214']);

        $this->actingAs($this->counselor)
            ->post(route('counselor.students.store'), $this->validStudent([
                'first_name' => '',
                'birth_date' => now()->addDay()->toDateString(),
                'school_year_id' => $this->currentYear->id,
            ]))
            ->assertSessionHasErrors(['lrn', 'first_name', 'birth_date', 'section_id']);

        $this->actingAs($this->counselor)
            ->post(route('counselor.students.store'), $this->validStudent(['lrn' => '12345']))
            ->assertSessionHasErrors(['lrn' => 'The LRN must be exactly 12 digits.']);
    }

    public function test_the_list_shows_the_current_year_and_masks_lrns(): void
    {
        $enrolled = Student::factory()->create(['lrn' => '100000000214', 'last_name' => 'Villanueva']);
        StudentEnrollment::factory()->for($enrolled)->for($this->currentYear)->for($this->section)->create();
        Student::factory()->create(['last_name' => 'Notenrolled']);

        $this->actingAs($this->counselor)
            ->get(route('counselor.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('counselor/students/index')
                ->where('filters.year', (string) $this->currentYear->id)
                ->has('students.data', 1)
                ->where('students.data.0.maskedLrn', '•••• •••• 0214')
                ->missing('students.data.0.lrn'));

        $this->actingAs($this->counselor)
            ->get(route('counselor.students.index', ['year' => 'all']))
            ->assertInertia(fn (Assert $page) => $page->has('students.data', 2));
    }

    public function test_students_are_found_by_name_or_lrn(): void
    {
        Student::factory()->create(['first_name' => 'Andrea', 'last_name' => 'Villanueva', 'lrn' => '100000000214']);
        Student::factory()->create(['first_name' => 'Paolo', 'last_name' => 'Dizon', 'lrn' => '200000000377']);

        $search = fn (string $term) => $this->actingAs($this->counselor)->get(route('counselor.students.index', ['year' => 'all', 'q' => $term]));

        $search('villa')->assertInertia(fn (Assert $page) => $page->has('students.data', 1)->where('students.data.0.name', fn (string $name) => str_starts_with($name, 'Villanueva')));
        $search('Dizon, Paolo')->assertInertia(fn (Assert $page) => $page->has('students.data', 1));
        $search('2000')->assertInertia(fn (Assert $page) => $page->has('students.data', 1));
        $search('100%')->assertInertia(fn (Assert $page) => $page->has('students.data', 0));
    }

    public function test_the_profile_shows_enrollment_history_newest_first(): void
    {
        $student = Student::factory()->create();
        $lastYear = SchoolYear::factory()->startingIn(2025)->create();
        StudentEnrollment::factory()->for($student)->for($lastYear)->for(Section::factory()->grade(9))->create();
        StudentEnrollment::factory()->for($student)->for($this->currentYear)->for($this->section)->create();

        $this->actingAs($this->counselor)
            ->get(route('counselor.students.show', $student))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('counselor/students/show')
                ->has('enrollments', 2)
                ->where('enrollments.0.schoolYear', '2026–2027')
                ->where('enrollments.0.isCurrentYear', true)
                ->where('enrollments.1.gradeLevel', 9)
                ->has('schoolYears', 0));
    }

    public function test_a_counselor_corrects_a_student_and_only_field_names_are_logged(): void
    {
        $student = Student::factory()->create(['first_name' => 'Andrea']);
        $colleague = User::factory()->counselor()->create();

        $this->actingAs($colleague)
            ->put(route('counselor.students.update', $student), [
                'lrn' => $student->lrn,
                'first_name' => 'Andrea Mae',
                'last_name' => $student->last_name,
            ])
            ->assertRedirect(route('counselor.students.show', $student));

        $student->refresh();
        $this->assertSame('Andrea Mae', $student->first_name);
        $this->assertSame($colleague->id, $student->updated_by);

        $entry = AuditLog::where('action', AuditAction::RecordUpdated)->sole();
        $this->assertStringContainsString('first_name', $entry->description);
        $this->assertStringNotContainsString('Andrea Mae', $entry->description);
    }

    public function test_the_administrator_has_no_access_to_student_records(): void
    {
        $student = Student::factory()->create();
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator);

        $this->get(route('counselor.students.index'))->assertForbidden();
        $this->get(route('counselor.students.show', $student))->assertForbidden();
        $this->post(route('counselor.students.store'), $this->validStudent())->assertForbidden();
    }

    public function test_student_records_cannot_be_deleted(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($this->counselor)
            ->delete("/counselor/students/{$student->id}")
            ->assertMethodNotAllowed();

        $this->assertModelExists($student);
    }

    public function test_the_shared_ledger_shows_signed_student_entries(): void
    {
        $this->actingAs($this->counselor)
            ->post(route('counselor.students.store'), $this->validStudent());

        $this->actingAs(User::factory()->counselor()->create())
            ->get(route('counselor.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.studentsOnFile', 1)
                ->has('ledger', 1)
                ->where('ledger.0.signedBy', $this->counselor->name)
                ->where('ledger.0.recordLabel', 'Villanueva, Andrea R.')
                ->where('ledger.0.recordUrl', '/counselor/students/'.Student::sole()->id));
    }
}
