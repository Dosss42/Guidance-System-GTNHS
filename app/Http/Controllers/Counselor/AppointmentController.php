<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AppointmentPurpose;
use App\Enums\AppointmentStatus;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Counselor\Concerns\PresentsCasework;
use App\Http\Requests\Counselor\AppointmentRequest;
use App\Models\Appointment;
use App\Models\GuidanceCase;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    use PresentsCasework;

    /**
     * @var list<string>
     */
    private const VIEWS = ['upcoming', 'today', 'past'];

    /**
     * List appointments: upcoming (default), today, or past.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Appointment::class);

        $view = in_array($request->query('view'), self::VIEWS, true) ? $request->query('view') : 'upcoming';

        $appointments = Appointment::with(['student.currentEnrollment.section', 'guidanceCase:id,reference', 'creator:id,name', 'updater:id,name'])
            ->when($view === 'upcoming', fn ($query) => $query->where('scheduled_at', '>=', today())->orderBy('scheduled_at'))
            ->when($view === 'today', fn ($query) => $query->whereDate('scheduled_at', today())->orderBy('scheduled_at'))
            ->when($view === 'past', fn ($query) => $query->where('scheduled_at', '<', today())->orderByDesc('scheduled_at'))
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Appointment $appointment): array => self::present($appointment));

        return Inertia::render('counselor/appointments/index', [
            'appointments' => $appointments,
            'view' => $view,
        ]);
    }

    /**
     * Book an appointment. `?student=` and `?case=` prefill the form.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Appointment::class);

        $student = $request->integer('student') ? Student::with('currentEnrollment.section')->find($request->integer('student')) : null;
        $case = $request->integer('case') ? GuidanceCase::with('student.currentEnrollment.section')->find($request->integer('case')) : null;
        $student ??= $case?->student;

        return Inertia::render('counselor/appointments/form', [
            'appointment' => null,
            'student' => $student ? self::studentSummary($student) : null,
            'caseId' => $case?->id,
            ...$this->options($student),
        ]);
    }

    public function store(AppointmentRequest $request, AuditLogger $audit): RedirectResponse
    {
        $appointment = Appointment::create([
            ...$request->safe()->except('status'),
            'status' => AppointmentStatus::Scheduled,
        ]);

        $audit->record(AuditAction::RecordCreated, 'appointments', $appointment, $appointment->purpose->label());

        return redirect()->route('counselor.appointments.index', ['view' => $appointment->scheduled_at->isToday() ? 'today' : 'upcoming'])
            ->with('status', "Appointment booked for {$appointment->scheduled_at->format('M j, g:i A')}.");
    }

    /**
     * Update the schedule, record the outcome, or add remarks.
     */
    public function edit(Appointment $appointment): Response
    {
        Gate::authorize('update', $appointment);

        $appointment->load(['student.currentEnrollment.section', 'creator:id,name', 'updater:id,name']);

        return Inertia::render('counselor/appointments/form', [
            'appointment' => self::present($appointment),
            'student' => self::studentSummary($appointment->student),
            'caseId' => $appointment->guidance_case_id,
            ...$this->options($appointment->student),
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment, AuditLogger $audit): RedirectResponse
    {
        $original = ['status' => $appointment->status];

        $appointment->update($request->validated());

        if ($appointment->wasChanged()) {
            $audit->record(AuditAction::RecordUpdated, 'appointments', $appointment, 'Changed: '.self::describeChanges($appointment, $original));
        }

        $message = $appointment->status === AppointmentStatus::Completed && $appointment->guidance_case_id
            ? 'Appointment marked completed. Record the session on the case if it needs documentation.'
            : 'Appointment saved.';

        return redirect()->route('counselor.appointments.index', ['view' => $appointment->scheduled_at->isPast() && ! $appointment->scheduled_at->isToday() ? 'past' : 'upcoming'])
            ->with('status', $message);
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'scheduledAt' => $appointment->scheduled_at->toIso8601String(),
            'scheduledAtLocal' => $appointment->scheduled_at->format('Y-m-d\TH:i'),
            'durationMinutes' => $appointment->duration_minutes,
            'purpose' => $appointment->purpose->value,
            'purposeLabel' => $appointment->purpose->label(),
            'status' => $appointment->status->value,
            'statusLabel' => $appointment->status->label(),
            'remarks' => $appointment->remarks,
            'student' => $appointment->relationLoaded('student') ? self::studentSummary($appointment->student) : null,
            'caseId' => $appointment->guidance_case_id,
            'caseReference' => $appointment->relationLoaded('guidanceCase') ? $appointment->guidanceCase?->reference : null,
            ...self::signature($appointment),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function options(?Student $student): array
    {
        return [
            'purposes' => AppointmentPurpose::options(),
            'statuses' => AppointmentStatus::options(),
            'cases' => $student === null ? [] : GuidanceCase::where('student_id', $student->id)
                ->active()
                ->orderByDesc('opened_on')
                ->get(['id', 'reference', 'category'])
                ->map(fn (GuidanceCase $case): array => ['id' => $case->id, 'label' => "{$case->reference} · {$case->category->label()}"]),
        ];
    }
}
