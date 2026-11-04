<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Enums\ContactMethod;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Counselor\Concerns\PresentsCasework;
use App\Http\Requests\Counselor\StoreParentCoordinationRequest;
use App\Models\GuidanceCase;
use App\Models\ParentCoordination;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ParentCoordinationController extends Controller
{
    use PresentsCasework;

    /**
     * Recent parent and guardian contacts across all students.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', ParentCoordination::class);

        $coordinations = ParentCoordination::with(['student.currentEnrollment.section', 'guidanceCase:id,reference', 'creator:id,name', 'updater:id,name'])
            ->latest('coordinated_at')
            ->latest('id')
            ->paginate(25)
            ->through(fn (ParentCoordination $coordination): array => self::present($coordination));

        return Inertia::render('counselor/coordination/index', [
            'coordinations' => $coordinations,
        ]);
    }

    /**
     * Record a contact. `?student=` and `?case=` prefill the form.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', ParentCoordination::class);

        $case = $request->integer('case') ? GuidanceCase::with('student.currentEnrollment.section')->find($request->integer('case')) : null;
        $student = $case?->student
            ?? ($request->integer('student') ? Student::with('currentEnrollment.section')->find($request->integer('student')) : null);

        return Inertia::render('counselor/coordination/create', [
            'student' => $student ? self::studentSummary($student) : null,
            'caseId' => $case?->id,
            'cases' => $student === null ? [] : GuidanceCase::where('student_id', $student->id)
                ->orderByDesc('opened_on')
                ->get(['id', 'reference', 'category', 'status'])
                ->map(fn (GuidanceCase $item): array => ['id' => $item->id, 'label' => "{$item->reference} · {$item->category->label()} ({$item->status->label()})"]),
            'methods' => ContactMethod::options(),
            'now' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(StoreParentCoordinationRequest $request, AuditLogger $audit): RedirectResponse
    {
        $coordination = ParentCoordination::create($request->validated());

        $audit->record(AuditAction::RecordCreated, 'coordination', $coordination->student, "{$coordination->method->label()} with {$coordination->relationship}");

        if ($coordination->guidance_case_id !== null) {
            return redirect()->route('counselor.cases.show', $coordination->guidance_case_id)->with('status', 'Parent / guardian contact recorded on the case.');
        }

        return redirect()->route('counselor.students.show', $coordination->student_id)->with('status', 'Parent / guardian contact recorded.');
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(ParentCoordination $coordination): array
    {
        return [
            'id' => $coordination->id,
            'coordinatedAt' => $coordination->coordinated_at->toIso8601String(),
            'contactName' => $coordination->contact_name,
            'relationship' => $coordination->relationship,
            'method' => $coordination->method->label(),
            'purpose' => $coordination->purpose,
            'outcome' => $coordination->outcome,
            'student' => $coordination->relationLoaded('student') ? self::studentSummary($coordination->student) : null,
            'caseId' => $coordination->guidance_case_id,
            'caseReference' => $coordination->relationLoaded('guidanceCase') ? $coordination->guidanceCase?->reference : null,
            ...self::signature($coordination),
        ];
    }
}
