<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\StoreStudentRequest;
use App\Http\Requests\Counselor\UpdateStudentRequest;
use App\Models\Appointment;
use App\Models\Document;
use App\Models\GuidanceCase;
use App\Models\ParentCoordination;
use App\Models\Referral;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    /**
     * Search and list students. By default the list shows the current school year.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Student::class);

        $schoolYears = SchoolYear::orderByDesc('start_date')->get();
        $currentYear = $schoolYears->firstWhere('is_current', true);

        $yearFilter = $request->query('year', $currentYear?->id ? (string) $currentYear->id : 'all');
        $selectedYear = $yearFilter === 'all' ? null : $schoolYears->firstWhere('id', (int) $yearFilter);
        $grade = in_array((int) $request->query('grade'), Section::GRADE_LEVELS, true) ? (int) $request->query('grade') : null;

        $students = Student::query()
            ->search($request->string('q')->toString())
            ->when($selectedYear !== null || $grade !== null, fn (Builder $query) => $query->whereHas('enrollments', function (Builder $enrollment) use ($selectedYear, $grade): void {
                $enrollment
                    ->when($selectedYear !== null, fn (Builder $q) => $q->where('school_year_id', $selectedYear->id))
                    ->when($grade !== null, fn (Builder $q) => $q->whereHas('section', fn (Builder $section) => $section->where('grade_level', $grade)));
            }))
            ->with(['enrollments.schoolYear', 'enrollments.section', 'updater:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25)
            ->withQueryString()
            ->through(function (Student $student) use ($selectedYear): array {
                $enrollment = $selectedYear !== null
                    ? $student->enrollments->firstWhere('school_year_id', $selectedYear->id)
                    : $student->enrollments->sortByDesc(fn (StudentEnrollment $item) => $item->schoolYear->start_date)->first();

                return [
                    'id' => $student->id,
                    'name' => $student->filingName(),
                    'maskedLrn' => $student->maskedLrn(),
                    'enrollment' => $enrollment === null ? null : self::presentEnrollment($enrollment),
                    'updatedBy' => $student->updater?->name,
                    'updatedAt' => $student->updated_at?->toIso8601String(),
                ];
            });

        return Inertia::render('counselor/students/index', [
            'students' => $students,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'year' => $selectedYear?->id ? (string) $selectedYear->id : 'all',
                'grade' => $grade === null ? '' : (string) $grade,
            ],
            'schoolYears' => $schoolYears->map(fn (SchoolYear $year): array => [
                'id' => $year->id,
                'name' => $year->name,
                'isCurrent' => $year->is_current,
            ]),
            'gradeLevels' => Section::GRADE_LEVELS,
        ]);
    }

    /**
     * Show the form for a new student record.
     */
    public function create(): Response
    {
        Gate::authorize('create', Student::class);

        return Inertia::render('counselor/students/create', $this->enrollmentOptions());
    }

    /**
     * Save a new student and, when given, their enrollment for one school year.
     */
    public function store(StoreStudentRequest $request, AuditLogger $audit): RedirectResponse
    {
        $student = DB::transaction(function () use ($request, $audit): Student {
            $student = Student::create($request->safe()->only(['lrn', 'first_name', 'middle_name', 'last_name', 'suffix', 'birth_date']));

            $audit->record(AuditAction::RecordCreated, 'students', $student);

            if ($request->filled('school_year_id')) {
                $enrollment = $student->enrollments()->create([
                    'school_year_id' => $request->integer('school_year_id'),
                    'section_id' => $request->integer('section_id'),
                    'status' => $request->validated('status') ?? EnrollmentStatus::Enrolled->value,
                ]);

                $audit->record(AuditAction::RecordCreated, 'enrollments', $student, self::describeEnrollment($enrollment));
            }

            return $student;
        });

        return redirect()->route('counselor.students.show', $student)
            ->with('status', "Student record created for {$student->filingName()}.");
    }

    /**
     * Show a student's record card and enrollment history.
     */
    public function show(Student $student): Response
    {
        Gate::authorize('view', $student);

        $student->load(['enrollments.schoolYear', 'enrollments.section', 'enrollments.updater:id,name', 'creator:id,name', 'updater:id,name']);

        $enrollments = $student->enrollments
            ->sortByDesc(fn (StudentEnrollment $enrollment) => $enrollment->schoolYear->start_date)
            ->values();

        $usedYearIds = $enrollments->pluck('school_year_id');

        return Inertia::render('counselor/students/show', [
            'student' => [
                ...$this->presentIdentity($student),
                'filingName' => $student->filingName(),
                'maskedLrn' => $student->maskedLrn(),
                'createdBy' => $student->creator?->name,
                'createdAt' => $student->created_at?->toIso8601String(),
                'updatedBy' => $student->updater?->name,
                'updatedAt' => $student->updated_at?->toIso8601String(),
            ],
            'enrollments' => $enrollments->map(fn (StudentEnrollment $enrollment): array => [
                ...self::presentEnrollment($enrollment),
                'updatedBy' => $enrollment->updater?->name,
            ]),
            ...$this->enrollmentOptions(excludeYearIds: $usedYearIds->all()),
            'casework' => [
                'cases' => $student->guidanceCases()->withCount('sessions')->orderByDesc('opened_on')->get()
                    ->map(fn (GuidanceCase $case): array => [
                        'id' => $case->id,
                        'reference' => $case->reference,
                        'category' => $case->category->label(),
                        'status' => $case->status->value,
                        'statusLabel' => $case->status->label(),
                        'openedOn' => $case->opened_on->toDateString(),
                        'sessionCount' => $case->sessions_count,
                    ]),
                'referrals' => $student->referrals()->orderByDesc('received_on')->limit(10)->get()
                    ->map(fn (Referral $referral): array => [
                        'id' => $referral->id,
                        'reference' => $referral->reference,
                        'category' => $referral->category->label(),
                        'receivedOn' => $referral->received_on->toDateString(),
                        'referringTeacher' => $referral->referring_teacher,
                        'status' => $referral->status->value,
                        'statusLabel' => $referral->status->label(),
                    ]),
                'appointments' => $student->appointments()->with('creator:id,name')->orderByDesc('scheduled_at')->limit(10)->get()
                    ->map(fn (Appointment $appointment): array => [
                        'id' => $appointment->id,
                        'scheduledAt' => $appointment->scheduled_at->toIso8601String(),
                        'purpose' => $appointment->purpose->label(),
                        'status' => $appointment->status->value,
                        'statusLabel' => $appointment->status->label(),
                        'signedBy' => $appointment->creator?->name,
                    ]),
                'documents' => Document::where('student_id', $student->id)->with(['uploader:id,name', 'guidanceCase:id,reference'])->latest()->limit(10)->get()
                    ->map(fn (Document $document): array => DocumentController::present($document)),
                'coordinations' => $student->coordinations()->with('creator:id,name')->orderByDesc('coordinated_at')->limit(5)->get()
                    ->map(fn (ParentCoordination $coordination): array => [
                        'id' => $coordination->id,
                        'coordinatedAt' => $coordination->coordinated_at->toIso8601String(),
                        'contactName' => $coordination->contact_name,
                        'relationship' => $coordination->relationship,
                        'method' => $coordination->method->label(),
                        'signedBy' => $coordination->creator?->name,
                    ]),
            ],
        ]);
    }

    /**
     * Show the form for correcting a student's details.
     */
    public function edit(Student $student): Response
    {
        Gate::authorize('update', $student);

        return Inertia::render('counselor/students/edit', [
            'student' => $this->presentIdentity($student) + ['filingName' => $student->filingName()],
        ]);
    }

    /**
     * Save corrections to a student's details. Only changed field names are logged.
     */
    public function update(UpdateStudentRequest $request, Student $student, AuditLogger $audit): RedirectResponse
    {
        $student->fill($request->validated());

        if (! $student->isDirty()) {
            return redirect()->route('counselor.students.show', $student)->with('status', 'No changes to save.');
        }

        $changedFields = implode(', ', array_keys($student->getDirty()));

        $student->save();

        $audit->record(AuditAction::RecordUpdated, 'students', $student, "Changed: {$changedFields}");

        return redirect()->route('counselor.students.show', $student)
            ->with('status', 'Student details saved.');
    }

    /**
     * @return array{id: int, lrn: string, firstName: string, middleName: ?string, lastName: string, suffix: ?string, birthDate: ?string}
     */
    private function presentIdentity(Student $student): array
    {
        return [
            'id' => $student->id,
            'lrn' => $student->lrn,
            'firstName' => $student->first_name,
            'middleName' => $student->middle_name,
            'lastName' => $student->last_name,
            'suffix' => $student->suffix,
            'birthDate' => $student->birth_date?->toDateString(),
        ];
    }

    /**
     * School years, sections and statuses for enrollment pickers.
     *
     * @param  list<int>  $excludeYearIds
     * @return array<string, mixed>
     */
    private function enrollmentOptions(array $excludeYearIds = []): array
    {
        $schoolYears = SchoolYear::orderByDesc('start_date')->get();

        return [
            'schoolYears' => $schoolYears
                ->reject(fn (SchoolYear $year) => in_array($year->id, $excludeYearIds, true))
                ->values()
                ->map(fn (SchoolYear $year): array => ['id' => $year->id, 'name' => $year->name, 'isCurrent' => $year->is_current]),
            'sections' => Section::orderBy('grade_level')->orderBy('name')->get()
                ->map(fn (Section $section): array => ['id' => $section->id, 'gradeLevel' => $section->grade_level, 'name' => $section->name]),
            'statuses' => EnrollmentStatus::options(),
        ];
    }

    /**
     * @return array{id: int, schoolYearId: int, schoolYear: string, isCurrentYear: bool, sectionId: int, gradeLevel: int, section: string, status: string, statusLabel: string}
     */
    public static function presentEnrollment(StudentEnrollment $enrollment): array
    {
        return [
            'id' => $enrollment->id,
            'schoolYearId' => $enrollment->school_year_id,
            'schoolYear' => $enrollment->schoolYear->name,
            'isCurrentYear' => $enrollment->schoolYear->is_current,
            'sectionId' => $enrollment->section_id,
            'gradeLevel' => $enrollment->section->grade_level,
            'section' => $enrollment->section->name,
            'status' => $enrollment->status->value,
            'statusLabel' => $enrollment->status->label(),
        ];
    }

    /**
     * A short, non-sensitive audit description such as "SY 2026–2027 · Grade 10 · Sampaguita · Enrolled".
     */
    public static function describeEnrollment(StudentEnrollment $enrollment): string
    {
        $enrollment->loadMissing(['schoolYear', 'section']);

        return "SY {$enrollment->schoolYear->name} · {$enrollment->section->label()} · {$enrollment->status->label()}";
    }
}
