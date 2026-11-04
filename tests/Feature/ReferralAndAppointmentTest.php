<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\ReferralStatus;
use App\Models\Appointment;
use App\Models\GuidanceCase;
use App\Models\ParentCoordination;
use App\Models\Referral;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReferralAndAppointmentTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    private function referralInput(array $overrides = []): array
    {
        return [
            'student_id' => $this->student->id,
            'received_on' => today()->toDateString(),
            'referring_teacher' => 'Ms. Leonora Cruz',
            'teacher_role' => 'Adviser',
            'received_via' => 'paper_slip',
            'category' => 'attendance',
            'priority' => 'routine',
            'reason' => 'Sample reason.',
            ...$overrides,
        ];
    }

    public function test_a_counselor_encodes_a_referral_with_a_reference(): void
    {
        $this->actingAs($this->counselor)
            ->post(route('counselor.referrals.store'), $this->referralInput())
            ->assertRedirect();

        $referral = Referral::sole();
        $this->assertSame(ReferralStatus::Received, $referral->status);
        $this->assertStringStartsWith('RF-'.now()->year.'-', $referral->reference);
        $this->assertSame($this->counselor->id, $referral->created_by);
    }

    public function test_a_referral_can_open_a_case_in_one_step(): void
    {
        $this->actingAs($this->counselor)
            ->post(route('counselor.referrals.store'), $this->referralInput(['open_case' => '1']))
            ->assertRedirect(route('counselor.cases.show', GuidanceCase::sole()));

        $this->assertTrue(GuidanceCase::sole()->referral->is(Referral::sole()));
        $this->assertSame(ReferralStatus::UnderReview, Referral::sole()->status);
    }

    public function test_referral_dates_cannot_be_in_the_future(): void
    {
        $this->actingAs($this->counselor)
            ->post(route('counselor.referrals.store'), $this->referralInput(['received_on' => today()->addDay()->toDateString()]))
            ->assertSessionHasErrors(['received_on' => "The date can't be later than today."]);
    }

    public function test_closing_a_referral_needs_an_outcome(): void
    {
        $referral = Referral::factory()->create();

        $this->actingAs($this->counselor)
            ->patch(route('counselor.referrals.update', $referral), ['status' => 'closed'])
            ->assertSessionHasErrors('review_outcome');

        $this->actingAs($this->counselor)
            ->patch(route('counselor.referrals.update', $referral), ['status' => 'closed', 'review_outcome' => 'Resolved with adviser.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(ReferralStatus::Closed, $referral->fresh()->status);
    }

    public function test_a_second_counselor_sees_and_updates_a_referral_encoded_by_the_first(): void
    {
        $this->actingAs($this->counselor)->post(route('counselor.referrals.store'), $this->referralInput());
        $referral = Referral::sole();
        $colleague = User::factory()->counselor()->create();

        $this->actingAs($colleague)
            ->get(route('counselor.referrals.show', $referral))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('referral.createdBy', $this->counselor->name));

        $this->actingAs($colleague)
            ->patch(route('counselor.referrals.update', $referral), ['status' => 'under_review']);

        $referral->refresh();
        $this->assertSame($this->counselor->id, $referral->created_by);
        $this->assertSame($colleague->id, $referral->updated_by);
    }

    public function test_a_counselor_books_an_appointment(): void
    {
        $when = today()->addDay()->setTime(9, 0);

        $this->actingAs($this->counselor)
            ->post(route('counselor.appointments.store'), [
                'student_id' => $this->student->id,
                'scheduled_at' => $when->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'purpose' => 'counseling',
            ])
            ->assertRedirect();

        $appointment = Appointment::sole();
        $this->assertSame(AppointmentStatus::Scheduled, $appointment->status);
        $this->assertTrue($appointment->scheduled_at->equalTo($when));
    }

    public function test_overlapping_appointments_for_the_same_student_are_blocked(): void
    {
        Appointment::factory()->for($this->student)->create(['scheduled_at' => today()->addDay()->setTime(9, 0), 'duration_minutes' => 30]);

        $this->actingAs($this->counselor)
            ->post(route('counselor.appointments.store'), [
                'student_id' => $this->student->id,
                'scheduled_at' => today()->addDay()->setTime(9, 15)->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'purpose' => 'counseling',
            ])
            ->assertSessionHasErrors('scheduled_at');

        $this->actingAs($this->counselor)
            ->post(route('counselor.appointments.store'), [
                'student_id' => $this->student->id,
                'scheduled_at' => today()->addDay()->setTime(9, 30)->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'purpose' => 'counseling',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Appointment::count());
    }

    public function test_an_appointment_outcome_is_recorded(): void
    {
        $appointment = Appointment::factory()->for($this->student)->create();

        $this->actingAs($this->counselor)
            ->put(route('counselor.appointments.update', $appointment), [
                'student_id' => $this->student->id,
                'scheduled_at' => $appointment->scheduled_at->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'purpose' => $appointment->purpose->value,
                'status' => 'no_show',
            ])
            ->assertRedirect();

        $this->assertSame(AppointmentStatus::NoShow, $appointment->fresh()->status);
    }

    public function test_an_appointment_cannot_link_another_students_case(): void
    {
        $otherCase = GuidanceCase::factory()->create();

        $this->actingAs($this->counselor)
            ->post(route('counselor.appointments.store'), [
                'student_id' => $this->student->id,
                'guidance_case_id' => $otherCase->id,
                'scheduled_at' => today()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'),
                'duration_minutes' => 30,
                'purpose' => 'counseling',
            ])
            ->assertSessionHasErrors('guidance_case_id');
    }

    public function test_a_parent_contact_is_recorded_on_a_case(): void
    {
        $case = GuidanceCase::factory()->for($this->student)->create();

        $this->actingAs($this->counselor)
            ->post(route('counselor.coordination.store'), [
                'student_id' => $this->student->id,
                'guidance_case_id' => $case->id,
                'coordinated_at' => now()->subHour()->format('Y-m-d\TH:i'),
                'contact_name' => 'Rosario Villanueva',
                'relationship' => 'Mother',
                'method' => 'phone',
                'purpose' => 'Invite to meeting',
            ])
            ->assertRedirect(route('counselor.cases.show', $case));

        $this->assertSame($this->counselor->id, ParentCoordination::sole()->created_by);
    }

    public function test_the_student_lookup_finds_students_and_keeps_lrns_masked(): void
    {
        $student = Student::factory()->create(['last_name' => 'Villanueva', 'lrn' => '100000000214']);

        $this->actingAs($this->counselor)
            ->getJson(route('counselor.student-lookup', ['q' => 'villa']))
            ->assertOk()
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.id', $student->id)
            ->assertJsonPath('students.0.maskedLrn', '•••• •••• 0214')
            ->assertJsonMissingPath('students.0.lrn');
    }

    public function test_the_administrator_has_no_access_to_any_casework(): void
    {
        $referral = Referral::factory()->create();
        $case = GuidanceCase::factory()->create();
        $appointment = Appointment::factory()->create();

        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('counselor.referrals.index'))->assertForbidden();
        $this->get(route('counselor.referrals.show', $referral))->assertForbidden();
        $this->get(route('counselor.cases.show', $case))->assertForbidden();
        $this->get(route('counselor.appointments.edit', $appointment))->assertForbidden();
        $this->get(route('counselor.follow-ups.index'))->assertForbidden();
        $this->get(route('counselor.coordination.index'))->assertForbidden();
        $this->getJson(route('counselor.student-lookup', ['q' => 'ab']))->assertForbidden();
    }

    public function test_casework_records_cannot_be_deleted(): void
    {
        $case = GuidanceCase::factory()->create();
        $referral = Referral::factory()->create();

        $this->actingAs($this->counselor);

        $this->delete("/counselor/cases/{$case->id}")->assertMethodNotAllowed();
        $this->delete("/counselor/referrals/{$referral->id}")->assertMethodNotAllowed();

        $this->assertModelExists($case);
        $this->assertModelExists($referral);
    }
}
