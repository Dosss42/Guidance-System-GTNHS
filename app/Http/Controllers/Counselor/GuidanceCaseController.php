<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Enums\CaseStatus;
use App\Enums\ConcernCategory;
use App\Enums\ContactMethod;
use App\Enums\FollowUpStatus;
use App\Enums\ReferralStatus;
use App\Enums\SessionType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Counselor\Concerns\PresentsCasework;
use App\Http\Requests\Counselor\StoreGuidanceCaseRequest;
use App\Http\Requests\Counselor\UpdateGuidanceCaseRequest;
use App\Models\Appointment;
use App\Models\CounselingSession;
use App\Models\Document;
use App\Models\FollowUp;
use App\Models\GuidanceCase;
use App\Models\ParentCoordination;
use App\Models\Referral;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GuidanceCaseController extends Controller
{
    use PresentsCasework;

    /**
     * List cases. By default only active ones (open, in progress, on hold).
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', GuidanceCase::class);

        $status = $request->query('status', 'active');
        $statusFilter = CaseStatus::tryFrom((string) $status);

        $cases = GuidanceCase::with(['student.currentEnrollment.section', 'creator:id,name', 'updater:id,name'])
            ->withCount('sessions')
            ->withMin(['followUps as next_follow_up_on' => fn ($query) => $query->where('status', FollowUpStatus::Pending)], 'due_on')
            ->when($status === 'active', fn ($query) => $query->active())
            ->when($statusFilter !== null, fn ($query) => $query->where('status', $statusFilter))
            ->orderByDesc('opened_on')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (GuidanceCase $case): array => [
                ...self::presentSummary($case),
                'sessionCount' => $case->sessions_count,
                'nextFollowUpOn' => $case->next_follow_up_on,
                'followUpOverdue' => $case->next_follow_up_on !== null && $case->next_follow_up_on < today()->toDateString(),
            ]);

        return Inertia::render('counselor/cases/index', [
            'cases' => $cases,
            'status' => $statusFilter?->value ?? ($status === 'all' ? 'all' : 'active'),
            'statuses' => CaseStatus::options(),
        ]);
    }

    /**
     * Open a case. `?student=` or `?referral=` prefill the form.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', GuidanceCase::class);

        $referral = $request->integer('referral') ? Referral::with('student.currentEnrollment.section')->doesntHave('guidanceCase')->find($request->integer('referral')) : null;
        $student = $referral?->student
            ?? ($request->integer('student') ? Student::with('currentEnrollment.section')->find($request->integer('student')) : null);

        return Inertia::render('counselor/cases/create', [
            'student' => $student ? self::studentSummary($student) : null,
            'referral' => $referral ? ['id' => $referral->id, 'reference' => $referral->reference, 'category' => $referral->category->value] : null,
            'openReferrals' => $student === null ? [] : Referral::where('student_id', $student->id)
                ->doesntHave('guidanceCase')
                ->where('status', '!=', ReferralStatus::Closed)
                ->latest('received_on')
                ->get()
                ->map(fn (Referral $item): array => ['id' => $item->id, 'label' => "{$item->reference} · {$item->category->label()}"]),
            'categories' => ConcernCategory::options(),
            'today' => today()->toDateString(),
        ]);
    }

    public function store(StoreGuidanceCaseRequest $request, AuditLogger $audit): RedirectResponse
    {
        $case = DB::transaction(function () use ($request, $audit): GuidanceCase {
            $case = GuidanceCase::create([...$request->validated(), 'status' => CaseStatus::Open]);
            $case->refresh();

            $referral = $case->referral;

            if ($referral !== null && $referral->status === ReferralStatus::Received) {
                $referral->update(['status' => ReferralStatus::UnderReview]);
            }

            $audit->record(
                AuditAction::RecordCreated,
                'cases',
                $case,
                $referral ? "Opened from referral {$referral->reference}" : $case->category->label(),
            );

            return $case;
        });

        return redirect()->route('counselor.cases.show', $case)->with('status', "Case {$case->reference} opened.");
    }

    /**
     * The case folder: details, a merged history, and forms to add to it.
     * Session notes are never included; they are unsealed separately.
     */
    public function show(GuidanceCase $case): Response
    {
        Gate::authorize('view', $case);

        $case->load([
            'student.currentEnrollment.section',
            'referral:id,reference,referring_teacher,received_on',
            'creator:id,name',
            'updater:id,name',
            'sessions' => fn ($query) => $query->select(['id', 'guidance_case_id', 'session_at', 'session_type', 'created_by', 'created_at'])->with('creator:id,name'),
            'followUps.creator:id,name',
            'followUps.updater:id,name',
            'coordinations.creator:id,name',
            'appointments.creator:id,name',
        ]);

        $history = collect()
            ->concat($case->sessions->map(fn (CounselingSession $session): array => [
                'key' => "session-{$session->id}",
                'kind' => 'session',
                'id' => $session->id,
                'at' => $session->session_at->toIso8601String(),
                'title' => $session->session_type->label(),
                'signedBy' => $session->creator?->name,
            ]))
            ->concat($case->followUps->map(fn (FollowUp $followUp): array => [
                'key' => "follow-up-{$followUp->id}",
                'kind' => 'follow_up',
                'id' => $followUp->id,
                'at' => $followUp->due_on->toDateString(),
                'title' => $followUp->purpose,
                'status' => $followUp->status->value,
                'statusLabel' => $followUp->isOverdue() ? 'Overdue' : $followUp->status->label(),
                'isOverdue' => $followUp->isOverdue(),
                'outcome' => $followUp->outcome,
                'signedBy' => $followUp->updater?->name ?? $followUp->creator?->name,
            ]))
            ->concat($case->coordinations->map(fn (ParentCoordination $coordination): array => [
                'key' => "coordination-{$coordination->id}",
                'kind' => 'coordination',
                'id' => $coordination->id,
                'at' => $coordination->coordinated_at->toIso8601String(),
                'title' => "{$coordination->method->label()} with {$coordination->contact_name} ({$coordination->relationship})",
                'detail' => $coordination->purpose,
                'outcome' => $coordination->outcome,
                'signedBy' => $coordination->creator?->name,
            ]))
            ->concat($case->appointments->map(fn (Appointment $appointment): array => [
                'key' => "appointment-{$appointment->id}",
                'kind' => 'appointment',
                'id' => $appointment->id,
                'at' => $appointment->scheduled_at->toIso8601String(),
                'title' => "Appointment · {$appointment->purpose->label()}",
                'status' => $appointment->status->value,
                'statusLabel' => $appointment->status->label(),
                'signedBy' => $appointment->creator?->name,
            ]))
            ->push([
                'key' => 'opened',
                'kind' => 'opened',
                'id' => $case->id,
                'at' => $case->opened_on->toDateString(),
                'title' => $case->referral ? "Case opened from referral {$case->referral->reference}" : 'Case opened',
                'detail' => $case->referral ? "Referred by {$case->referral->referring_teacher}" : null,
                'referralId' => $case->referral?->id,
                'signedBy' => $case->creator?->name,
            ])
            ->sortByDesc('at')
            ->values();

        $nextFollowUp = $case->followUps->where('status', FollowUpStatus::Pending)->sortBy('due_on')->first();

        return Inertia::render('counselor/cases/show', [
            'guidanceCase' => [
                ...self::presentSummary($case),
                'category' => $case->category->value,
                'summary' => $case->summary,
                'closedOn' => $case->closed_on?->toDateString(),
                'closureSummary' => $case->closure_summary,
                'referral' => $case->referral ? ['id' => $case->referral->id, 'reference' => $case->referral->reference] : null,
                'nextFollowUp' => $nextFollowUp ? ['dueOn' => $nextFollowUp->due_on->toDateString(), 'isOverdue' => $nextFollowUp->isOverdue()] : null,
                'signers' => self::signers($case),
            ],
            'history' => $history,
            'documents' => Document::where('guidance_case_id', $case->id)
                ->with('uploader:id,name')
                ->latest()
                ->get()
                ->map(fn (Document $document): array => DocumentController::present($document)),
            'statuses' => CaseStatus::options(),
            'categories' => ConcernCategory::options(),
            'sessionTypes' => SessionType::options(),
            'contactMethods' => ContactMethod::options(),
            'now' => now()->format('Y-m-d\TH:i'),
            'today' => today()->toDateString(),
        ]);
    }

    /**
     * Change status or details. Closing records today's date; reopening clears it.
     */
    public function update(UpdateGuidanceCaseRequest $request, GuidanceCase $case, AuditLogger $audit): RedirectResponse
    {
        $original = ['status' => $case->status];
        $status = CaseStatus::from($request->validated('status'));

        $case->fill([
            'status' => $status,
            'category' => $request->validated('category'),
            'summary' => $request->validated('summary'),
            'closure_summary' => $status === CaseStatus::Closed ? $request->validated('closure_summary') : $case->closure_summary,
            'closed_on' => $status === CaseStatus::Closed ? ($case->closed_on ?? today()) : null,
        ])->save();

        if ($case->wasChanged()) {
            $audit->record(AuditAction::RecordUpdated, 'cases', $case, 'Changed: '.self::describeChanges($case, $original));
        }

        return back()->with('status', $status === CaseStatus::Closed ? "Case {$case->reference} closed." : "Case {$case->reference} saved.");
    }

    /**
     * @return array<string, mixed>
     */
    private static function presentSummary(GuidanceCase $case): array
    {
        return [
            'id' => $case->id,
            'reference' => $case->reference,
            'categoryLabel' => $case->category->label(),
            'status' => $case->status->value,
            'statusLabel' => $case->status->label(),
            'openedOn' => $case->opened_on->toDateString(),
            'student' => self::studentSummary($case->student),
            ...self::signature($case),
        ];
    }

    /**
     * Who has signed entries on this case, with counts. Shows shared work, not ownership.
     *
     * @return list<array{name: string, entries: int}>
     */
    private static function signers(GuidanceCase $case): array
    {
        return collect([$case->creator?->name])
            ->concat($case->sessions->map(fn (CounselingSession $session) => $session->creator?->name))
            ->concat($case->followUps->map(fn (FollowUp $followUp) => $followUp->creator?->name))
            ->concat($case->coordinations->map(fn (ParentCoordination $coordination) => $coordination->creator?->name))
            ->filter()
            ->countBy()
            ->map(fn (int $entries, string $name): array => ['name' => $name, 'entries' => $entries])
            ->values()
            ->all();
    }
}
