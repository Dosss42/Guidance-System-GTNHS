<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Counselor\Concerns\PresentsCasework;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\FollowUp;
use App\Models\GuidanceCase;
use App\Models\Referral;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use PresentsCasework;

    /**
     * Modules whose signed entries appear in the counselors' shared ledger.
     *
     * @var list<string>
     */
    private const LEDGER_MODULES = ['students', 'enrollments', 'school_years', 'sections', 'appointments', 'referrals', 'cases', 'sessions', 'follow_ups', 'coordination', 'documents'];

    /**
     * Today's desk: the docket, what needs attention, counts and the shared ledger.
     */
    public function __invoke(): Response
    {
        $currentYear = SchoolYear::current();

        $docket = Appointment::with(['student.currentEnrollment.section', 'creator:id,name', 'updater:id,name', 'guidanceCase:id,reference'])
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Appointment $appointment): array => AppointmentController::present($appointment));

        $overdueFollowUps = FollowUp::overdue()
            ->with(['guidanceCase:id,reference,student_id', 'guidanceCase.student'])
            ->orderBy('due_on')
            ->limit(6)
            ->get()
            ->map(fn (FollowUp $followUp): array => [
                'id' => $followUp->id,
                'dueOn' => $followUp->due_on->toDateString(),
                'daysOverdue' => (int) $followUp->due_on->diffInDays(today()),
                'purpose' => $followUp->purpose,
                'caseId' => $followUp->guidance_case_id,
                'caseReference' => $followUp->guidanceCase->reference,
                'studentName' => $followUp->guidanceCase->student->filingName(),
            ]);

        $referralsToReview = Referral::whereIn('status', ReferralStatus::awaitingReview())
            ->with('student')
            ->orderByDesc('received_on')
            ->limit(6)
            ->get()
            ->map(fn (Referral $referral): array => [
                'id' => $referral->id,
                'reference' => $referral->reference,
                'receivedOn' => $referral->received_on->toDateString(),
                'referringTeacher' => $referral->referring_teacher,
                'studentName' => $referral->student->filingName(),
            ]);

        return Inertia::render('counselor/dashboard', [
            'today' => now()->toDateString(),
            'now' => now()->toIso8601String(),
            'counts' => [
                'appointmentsToday' => $docket->count(),
                'referralsToReview' => Referral::whereIn('status', ReferralStatus::awaitingReview())->count(),
                'followUpsOverdue' => FollowUp::overdue()->count(),
                'activeCases' => GuidanceCase::active()->count(),
                'studentsOnFile' => Student::count(),
                'currentSchoolYear' => $currentYear?->name,
            ],
            'docket' => $docket,
            'overdueFollowUps' => $overdueFollowUps,
            'referralsToReview' => $referralsToReview,
            'ledger' => $this->ledger(),
        ]);
    }

    /**
     * The latest signed record entries, with a link to each record.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function ledger(): Collection
    {
        $entries = AuditLog::with('user:id,name')
            ->whereIn('action', [AuditAction::RecordCreated, AuditAction::RecordUpdated, AuditAction::DocumentUploaded])
            ->whereIn('module', self::LEDGER_MODULES)
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get();

        $idsFor = fn (string $type) => $entries->where('record_type', $type)->pluck('record_id')->unique();

        $documents = Document::with('student')->whereIn('id', $idsFor('documents'))->get()->keyBy('id');

        $labels = collect([
            'students' => Student::whereIn('id', $idsFor('students'))->get()->mapWithKeys(fn (Student $student) => [$student->id => $student->filingName()]),
            'documents' => $documents->map(fn (Document $document) => "{$document->title} · {$document->student->filingName()}"),
            'guidance_cases' => GuidanceCase::whereIn('id', $idsFor('guidance_cases'))->pluck('reference', 'id'),
            'referrals' => Referral::whereIn('id', $idsFor('referrals'))->pluck('reference', 'id'),
            'appointments' => Appointment::whereIn('id', $idsFor('appointments'))->get()->mapWithKeys(fn (Appointment $appointment) => [$appointment->id => 'Appointment, '.$appointment->scheduled_at->format('M j')]),
        ]);

        $paths = [
            'students' => '/counselor/students/%d',
            'guidance_cases' => '/counselor/cases/%d',
            'referrals' => '/counselor/referrals/%d',
            'appointments' => '/counselor/appointments/%d/edit',
        ];

        return $entries->map(fn (AuditLog $entry): array => [
            'id' => $entry->id,
            'at' => $entry->created_at->toIso8601String(),
            'signedBy' => $entry->user?->name,
            'summary' => self::summarise($entry),
            'recordLabel' => $labels->get($entry->record_type)?->get($entry->record_id),
            'recordUrl' => match (true) {
                $entry->record_type === 'documents' && $documents->has($entry->record_id) => '/counselor/students/'.$documents[$entry->record_id]->student_id,
                isset($paths[$entry->record_type]) && (bool) $labels->get($entry->record_type)?->has($entry->record_id) => sprintf($paths[$entry->record_type], $entry->record_id),
                default => null,
            },
        ]);
    }

    /**
     * A plain-language ledger line. Descriptions are never shown here, so
     * nothing beyond "what kind of entry" reaches the dashboard.
     */
    private static function summarise(AuditLog $entry): string
    {
        $created = $entry->action === AuditAction::RecordCreated;

        return match ($entry->module) {
            'students' => $created ? 'Added a student record' : 'Updated a student record',
            'enrollments' => $created ? 'Added an enrollment' : 'Updated an enrollment',
            'school_years' => $created ? 'Added a school year' : 'Updated a school year',
            'sections' => $created ? 'Added a section' : 'Updated a section',
            'appointments' => $created ? 'Booked an appointment' : 'Updated an appointment',
            'referrals' => $created ? 'Encoded a teacher referral' : 'Reviewed a referral',
            'cases' => $created ? 'Opened a guidance case' : 'Updated a guidance case',
            'sessions' => 'Recorded a counseling session (sealed)',
            'follow_ups' => $created ? 'Scheduled a follow-up' : 'Updated a follow-up',
            'coordination' => 'Logged a parent / guardian contact',
            'documents' => 'Uploaded a document',
            default => $entry->action->label(),
        };
    }
}
