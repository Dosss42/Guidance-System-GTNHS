<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Enums\CaseStatus;
use App\Enums\ConcernCategory;
use App\Enums\ReferralChannel;
use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Counselor\Concerns\PresentsCasework;
use App\Http\Requests\Counselor\StoreReferralRequest;
use App\Http\Requests\Counselor\UpdateReferralRequest;
use App\Models\GuidanceCase;
use App\Models\Referral;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    use PresentsCasework;

    /**
     * List referrals. By default only those not yet closed.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Referral::class);

        $status = $request->query('status', 'open');
        $statusFilter = ReferralStatus::tryFrom((string) $status);

        $referrals = Referral::with(['student.currentEnrollment.section', 'guidanceCase:id,referral_id,reference', 'creator:id,name', 'updater:id,name'])
            ->when($status === 'open', fn ($query) => $query->where('status', '!=', ReferralStatus::Closed))
            ->when($statusFilter !== null, fn ($query) => $query->where('status', $statusFilter))
            ->orderByRaw('case when status = ? then 0 else 1 end', [ReferralStatus::Received->value])
            ->orderByDesc('received_on')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Referral $referral): array => self::present($referral));

        return Inertia::render('counselor/referrals/index', [
            'referrals' => $referrals,
            'status' => $statusFilter?->value ?? ($status === 'all' ? 'all' : 'open'),
            'statuses' => ReferralStatus::options(),
        ]);
    }

    /**
     * The encode-a-referral form. `?student=` preselects the student.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Referral::class);

        $student = $request->integer('student') ? Student::with('currentEnrollment.section')->find($request->integer('student')) : null;

        return Inertia::render('counselor/referrals/create', [
            'student' => $student ? self::studentSummary($student) : null,
            'today' => today()->toDateString(),
            ...self::options(),
        ]);
    }

    /**
     * Save the referral and, when asked, open a guidance case from it.
     */
    public function store(StoreReferralRequest $request, AuditLogger $audit): RedirectResponse
    {
        [$referral, $case] = DB::transaction(function () use ($request, $audit): array {
            $referral = Referral::create($request->safe()->except('open_case'));

            $audit->record(AuditAction::RecordCreated, 'referrals', $referral, "{$referral->category->label()} · from {$referral->received_via->label()}");

            if (! $request->boolean('open_case')) {
                return [$referral, null];
            }

            $case = GuidanceCase::create([
                'student_id' => $referral->student_id,
                'referral_id' => $referral->id,
                'category' => $referral->category,
                'status' => CaseStatus::Open,
                'opened_on' => today(),
            ]);

            $referral->update(['status' => ReferralStatus::UnderReview]);

            $audit->record(AuditAction::RecordCreated, 'cases', $case->refresh(), "Opened from referral {$referral->refresh()->reference}");

            return [$referral, $case];
        });

        if ($case !== null) {
            return redirect()->route('counselor.cases.show', $case)
                ->with('status', "Referral {$referral->reference} saved and case {$case->reference} opened.");
        }

        return redirect()->route('counselor.referrals.show', $referral)
            ->with('status', "Referral {$referral->refresh()->reference} saved. It now appears in every counselor's “Needs attention” list.");
    }

    public function show(Referral $referral): Response
    {
        Gate::authorize('view', $referral);

        $referral->load(['student.currentEnrollment.section', 'guidanceCase:id,referral_id,reference,status', 'creator:id,name', 'updater:id,name']);

        return Inertia::render('counselor/referrals/show', [
            'referral' => [
                ...self::present($referral),
                'teacherRole' => $referral->teacher_role,
                'receivedVia' => $referral->received_via->label(),
                'priority' => $referral->priority->value,
                'priorityLabel' => $referral->priority->label(),
                'reason' => $referral->reason,
                'reviewOutcome' => $referral->review_outcome,
                'caseStatus' => $referral->guidanceCase?->status->label(),
            ],
            'statuses' => ReferralStatus::options(),
        ]);
    }

    /**
     * Record the review: a new status and, when closing, the outcome.
     */
    public function update(UpdateReferralRequest $request, Referral $referral, AuditLogger $audit): RedirectResponse
    {
        $original = ['status' => $referral->status];

        $referral->update($request->validated());

        if ($referral->wasChanged()) {
            $audit->record(AuditAction::RecordUpdated, 'referrals', $referral, 'Changed: '.self::describeChanges($referral, $original));
        }

        return back()->with('status', "Referral {$referral->reference} updated.");
    }

    /**
     * @return array<string, mixed>
     */
    private static function present(Referral $referral): array
    {
        return [
            'id' => $referral->id,
            'reference' => $referral->reference,
            'receivedOn' => $referral->received_on->toDateString(),
            'referringTeacher' => $referral->referring_teacher,
            'category' => $referral->category->label(),
            'status' => $referral->status->value,
            'statusLabel' => $referral->status->label(),
            'priority' => $referral->priority->value,
            'student' => self::studentSummary($referral->student),
            'caseId' => $referral->guidanceCase?->id,
            'caseReference' => $referral->guidanceCase?->reference,
            ...self::signature($referral),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function options(): array
    {
        return [
            'categories' => ConcernCategory::options(),
            'channels' => ReferralChannel::options(),
            'priorities' => ReferralPriority::options(),
        ];
    }
}
