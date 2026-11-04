<?php

namespace App\Services;

use App\Enums\AppointmentPurpose;
use App\Enums\AppointmentStatus;
use App\Enums\CaseStatus;
use App\Enums\ConcernCategory;
use App\Enums\ContactMethod;
use App\Enums\FollowUpStatus;
use App\Enums\ReferralStatus;
use App\Models\Appointment;
use App\Models\CounselingSession;
use App\Models\Document;
use App\Models\FollowUp;
use App\Models\GuidanceCase;
use App\Models\ParentCoordination;
use App\Models\Referral;
use App\Models\SchoolYear;
use App\Models\StudentEnrollment;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Aggregate guidance statistics for a period. Counts only: no student
 * names, no case details and never counseling note contents.
 */
class GuidanceReport
{
    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly ?SchoolYear $schoolYear = null,
    ) {}

    /**
     * @return array{totals: array<string, int|float|null>, breakdowns: list<array{key: string, title: string, rows: list<array{label: string, count: int}>}>}
     */
    public function toArray(): array
    {
        $referrals = Referral::whereBetween('received_on', [$this->from->toDateString(), $this->to->toDateString()]);
        $casesOpened = GuidanceCase::whereBetween('opened_on', [$this->from->toDateString(), $this->to->toDateString()]);
        $appointments = Appointment::whereBetween('scheduled_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()]);
        $followUpsDue = FollowUp::whereBetween('due_on', [$this->from->toDateString(), $this->to->toDateString()])
            ->where('status', '!=', FollowUpStatus::Rescheduled);
        $coordinations = ParentCoordination::whereBetween('coordinated_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()]);

        $followUpsDueCount = (clone $followUpsDue)->count();
        $followUpsCompleted = (clone $followUpsDue)->where('status', FollowUpStatus::Completed)->count();
        $appointmentCount = (clone $appointments)->count();

        return [
            'totals' => [
                'referralsReceived' => (clone $referrals)->count(),
                'casesOpened' => (clone $casesOpened)->count(),
                'casesClosed' => GuidanceCase::where('status', CaseStatus::Closed)
                    ->whereBetween('closed_on', [$this->from->toDateString(), $this->to->toDateString()])
                    ->count(),
                'activeCasesNow' => GuidanceCase::active()->count(),
                'sessionsHeld' => CounselingSession::whereBetween('session_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()])->count(),
                'appointments' => $appointmentCount,
                'appointmentsCompleted' => (clone $appointments)->where('status', AppointmentStatus::Completed)->count(),
                'appointmentsNoShow' => (clone $appointments)->where('status', AppointmentStatus::NoShow)->count(),
                'followUpsDue' => $followUpsDueCount,
                'followUpsCompleted' => $followUpsCompleted,
                'followUpCompletionRate' => $followUpsDueCount === 0 ? null : round($followUpsCompleted / $followUpsDueCount * 100, 1),
                'parentContacts' => (clone $coordinations)->count(),
                'documentsUploaded' => Document::whereBetween('created_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()])->count(),
            ],
            'breakdowns' => array_values(array_filter([
                $this->breakdown('referral-categories', 'Referrals by category', $referrals, 'category', ConcernCategory::cases()),
                $this->breakdown('referral-statuses', 'Referrals by current status', $referrals, 'status', ReferralStatus::cases()),
                $this->breakdown('case-categories', 'Cases opened by category', $casesOpened, 'category', ConcernCategory::cases()),
                $this->breakdown('appointment-statuses', 'Appointments by outcome', $appointments, 'status', AppointmentStatus::cases()),
                $this->breakdown('appointment-purposes', 'Appointments by purpose', $appointments, 'purpose', AppointmentPurpose::cases()),
                $this->breakdown('contact-methods', 'Parent / guardian contacts by method', $coordinations, 'method', ContactMethod::cases()),
                $this->enrollmentByGrade(),
            ])),
        ];
    }

    /**
     * Counts per enum value, in the enum's own order, including zeros.
     *
     * @param  Builder<Model>  $query
     * @param  list<BackedEnum>  $cases
     * @return array{key: string, title: string, rows: list<array{label: string, count: int}>}
     */
    private function breakdown(string $key, string $title, Builder $query, string $column, array $cases): array
    {
        $counts = (clone $query)
            ->toBase()
            ->selectRaw("{$column} as bucket, count(*) as total")
            ->groupBy($column)
            ->pluck('total', 'bucket');

        return [
            'key' => $key,
            'title' => $title,
            'rows' => array_map(fn (BackedEnum $case): array => [
                'label' => method_exists($case, 'label') ? $case->label() : (string) $case->value,
                'count' => (int) ($counts[$case->value] ?? 0),
            ], $cases),
        ];
    }

    /**
     * Students enrolled per grade in the selected school year.
     *
     * @return array{key: string, title: string, rows: list<array{label: string, count: int}>}|null
     */
    private function enrollmentByGrade(): ?array
    {
        if ($this->schoolYear === null) {
            return null;
        }

        $counts = StudentEnrollment::where('school_year_id', $this->schoolYear->id)
            ->join('sections', 'sections.id', '=', 'student_enrollments.section_id')
            ->toBase()
            ->selectRaw('sections.grade_level as grade, count(*) as total')
            ->groupBy('sections.grade_level')
            ->pluck('total', 'grade');

        return [
            'key' => 'enrollment-grades',
            'title' => "Students enrolled in SY {$this->schoolYear->name} by grade",
            'rows' => array_map(fn (int $grade): array => ['label' => "Grade {$grade}", 'count' => (int) ($counts[$grade] ?? 0)], range(7, 12)),
        ];
    }

    public function periodLabel(): string
    {
        $range = $this->from->format('M j, Y').' – '.$this->to->format('M j, Y');

        return $this->schoolYear ? "SY {$this->schoolYear->name} ({$range})" : $range;
    }
}
