<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AuditAction;
use App\Enums\ConcernCategory;
use App\Enums\FollowUpStatus;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\FollowUp;
use App\Models\GuidanceCase;
use App\Models\Referral;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $counselor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counselor = User::factory()->counselor()->create();
    }

    public function test_the_report_counts_activity_in_the_selected_period(): void
    {
        $student = Student::factory()->create(['last_name' => 'Villanueva']);

        Referral::factory()->for($student)->count(2)->create(['received_on' => '2026-03-10', 'category' => ConcernCategory::Attendance]);
        Referral::factory()->for($student)->create(['received_on' => '2025-01-10']);
        GuidanceCase::factory()->for($student)->create(['opened_on' => '2026-03-11', 'category' => ConcernCategory::Academic]);
        Appointment::factory()->for($student)->create(['scheduled_at' => '2026-03-12 09:00', 'status' => AppointmentStatus::Completed]);
        Appointment::factory()->for($student)->create(['scheduled_at' => '2026-03-13 09:00', 'status' => AppointmentStatus::NoShow]);
        FollowUp::factory()->create(['due_on' => '2026-03-14', 'status' => FollowUpStatus::Completed]);
        FollowUp::factory()->create(['due_on' => '2026-03-15', 'status' => FollowUpStatus::Pending]);

        $response = $this->actingAs($this->counselor)
            ->get(route('counselor.reports.index', ['year' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-31']));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('counselor/reports/index')
            ->where('report.totals.referralsReceived', 2)
            ->where('report.totals.casesOpened', 1)
            ->where('report.totals.appointments', 2)
            ->where('report.totals.appointmentsNoShow', 1)
            ->where('report.totals.followUpCompletionRate', 50)
            ->where('report.breakdowns.0.key', 'referral-categories')
            ->where('report.breakdowns.0.rows.1.label', 'Attendance')
            ->where('report.breakdowns.0.rows.1.count', 2));

        $this->assertStringNotContainsString('Villanueva', $response->getContent());
    }

    public function test_the_report_defaults_to_the_current_school_year(): void
    {
        $year = SchoolYear::factory()->startingIn(2026)->current()->create();

        $this->actingAs($this->counselor)
            ->get(route('counselor.reports.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('period.year', (string) $year->id)
                ->where('period.from', '2026-06-15')
                ->where('report.breakdowns.6.key', 'enrollment-grades'));
    }

    public function test_custom_dates_are_validated(): void
    {
        $this->actingAs($this->counselor)
            ->get(route('counselor.reports.index', ['year' => 'custom', 'from' => '2026-05-01', 'to' => '2026-04-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_the_csv_export_is_logged_and_contains_counts_only(): void
    {
        $student = Student::factory()->create(['last_name' => 'Villanueva']);
        Referral::factory()->for($student)->create(['received_on' => '2026-03-10', 'reason' => 'Private reason text']);

        $response = $this->actingAs($this->counselor)
            ->get(route('counselor.reports.export', ['year' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-31']));

        $response->assertOk()->assertDownload('gtnhs-guidance-summary-2026-03-01-to-2026-03-31.csv');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('"Referrals received",1', $csv);
        $this->assertStringNotContainsString('Villanueva', $csv);
        $this->assertStringNotContainsString('Private reason text', $csv);

        $this->assertSame($this->counselor->id, AuditLog::where('action', AuditAction::ReportExported)->sole()->user_id);
    }

    public function test_the_administrator_cannot_open_or_export_reports(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('counselor.reports.index'))->assertForbidden();
        $this->get(route('counselor.reports.export'))->assertForbidden();
    }
}
