<?php

namespace Tests\Feature;

use App\Enums\CaseStatus;
use App\Enums\FollowUpStatus;
use App\Enums\ReferralStatus;
use App\Models\FollowUp;
use App\Models\GuidanceCase;
use App\Models\Referral;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuidanceCaseTest extends TestCase
{
    use RefreshDatabase;

    private User $counselor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counselor = User::factory()->counselor()->create();
    }

    public function test_a_counselor_opens_a_case_and_it_gets_a_reference(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($this->counselor)
            ->post(route('counselor.cases.store'), [
                'student_id' => $student->id,
                'category' => 'academic',
                'opened_on' => today()->toDateString(),
                'summary' => 'Sample summary.',
            ])
            ->assertRedirect();

        $case = GuidanceCase::sole();
        $this->assertSame(sprintf('GC-%d-%04d', now()->year, $case->id), $case->reference);
        $this->assertSame(CaseStatus::Open, $case->status);
        $this->assertSame($this->counselor->id, $case->created_by);
    }

    public function test_opening_a_case_from_a_referral_links_it_and_moves_the_referral_to_review(): void
    {
        $referral = Referral::factory()->create();

        $this->actingAs($this->counselor)
            ->post(route('counselor.cases.store'), [
                'student_id' => $referral->student_id,
                'referral_id' => $referral->id,
                'category' => $referral->category->value,
                'opened_on' => today()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertTrue(GuidanceCase::sole()->referral->is($referral));
        $this->assertSame(ReferralStatus::UnderReview, $referral->fresh()->status);
    }

    public function test_a_referral_cannot_be_linked_to_another_students_case_or_twice(): void
    {
        $referral = Referral::factory()->create();
        GuidanceCase::factory()->create(['student_id' => $referral->student_id, 'referral_id' => $referral->id]);

        $this->actingAs($this->counselor)
            ->post(route('counselor.cases.store'), [
                'student_id' => Student::factory()->create()->id,
                'referral_id' => $referral->id,
                'category' => 'academic',
                'opened_on' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('referral_id');

        $this->assertSame(1, GuidanceCase::count());
    }

    public function test_closing_a_case_needs_a_closure_summary_and_records_the_date(): void
    {
        $case = GuidanceCase::factory()->create();

        $this->actingAs($this->counselor)
            ->patch(route('counselor.cases.update', $case), ['status' => 'closed', 'category' => $case->category->value])
            ->assertSessionHasErrors('closure_summary');

        $this->actingAs($this->counselor)
            ->patch(route('counselor.cases.update', $case), [
                'status' => 'closed',
                'category' => $case->category->value,
                'closure_summary' => 'Goals met.',
            ])
            ->assertSessionHasNoErrors();

        $case->refresh();
        $this->assertTrue($case->isClosed());
        $this->assertTrue($case->closed_on->isToday());

        $this->actingAs($this->counselor)
            ->patch(route('counselor.cases.update', $case), ['status' => 'in_progress', 'category' => $case->category->value]);

        $this->assertNull($case->fresh()->closed_on);
    }

    public function test_the_case_list_shows_active_cases_by_default(): void
    {
        GuidanceCase::factory()->count(2)->create();
        GuidanceCase::factory()->closed()->create();

        $this->actingAs($this->counselor)
            ->get(route('counselor.cases.index'))
            ->assertInertia(fn (Assert $page) => $page->where('status', 'active')->has('cases.data', 2));

        $this->actingAs($this->counselor)
            ->get(route('counselor.cases.index', ['status' => 'all']))
            ->assertInertia(fn (Assert $page) => $page->has('cases.data', 3));
    }

    public function test_rescheduling_a_follow_up_keeps_the_old_one_and_creates_a_new_one(): void
    {
        $followUp = FollowUp::factory()->overdue()->create();
        $newDate = today()->addWeek()->toDateString();

        $this->actingAs($this->counselor)
            ->patch(route('counselor.follow-ups.update', $followUp), ['status' => 'rescheduled', 'due_on' => $newDate])
            ->assertSessionHasNoErrors();

        $this->assertSame(FollowUpStatus::Rescheduled, $followUp->fresh()->status);
        $replacement = FollowUp::where('status', FollowUpStatus::Pending)->sole();
        $this->assertSame($newDate, $replacement->due_on->toDateString());
        $this->assertSame($followUp->purpose, $replacement->purpose);
    }

    public function test_completing_a_follow_up_needs_an_outcome(): void
    {
        $followUp = FollowUp::factory()->create();

        $this->actingAs($this->counselor)
            ->patch(route('counselor.follow-ups.update', $followUp), ['status' => 'completed'])
            ->assertSessionHasErrors('outcome');

        $this->actingAs($this->counselor)
            ->patch(route('counselor.follow-ups.update', $followUp), ['status' => 'completed', 'outcome' => 'Adviser confirmed improvement.'])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($followUp->fresh()->completed_at);
    }

    public function test_overdue_follow_ups_and_unreviewed_referrals_reach_the_desk(): void
    {
        FollowUp::factory()->overdue(2)->create();
        FollowUp::factory()->create();
        Referral::factory()->create();
        Referral::factory()->status(ReferralStatus::Closed)->create();

        $this->actingAs($this->counselor)
            ->get(route('counselor.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.followUpsOverdue', 1)
                ->where('counts.referralsToReview', 1)
                ->has('overdueFollowUps', 1)
                ->where('overdueFollowUps.0.daysOverdue', 2)
                ->has('referralsToReview', 1));
    }
}
