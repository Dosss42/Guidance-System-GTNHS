<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\SchoolYearRequest;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolYearController extends Controller
{
    /**
     * Show the school years and sections that enrollments refer to.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', SchoolYear::class);

        return Inertia::render('counselor/school-years/index', [
            'schoolYears' => SchoolYear::withCount('enrollments')
                ->orderByDesc('start_date')
                ->get()
                ->map(fn (SchoolYear $year): array => [
                    'id' => $year->id,
                    'name' => $year->name,
                    'startDate' => $year->start_date->toDateString(),
                    'endDate' => $year->end_date->toDateString(),
                    'isCurrent' => $year->is_current,
                    'enrollmentCount' => $year->enrollments_count,
                ]),
            'sections' => Section::withCount('enrollments')
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get()
                ->map(fn (Section $section): array => [
                    'id' => $section->id,
                    'gradeLevel' => $section->grade_level,
                    'name' => $section->name,
                    'enrollmentCount' => $section->enrollments_count,
                ]),
            'gradeLevels' => Section::GRADE_LEVELS,
        ]);
    }

    /**
     * Add a school year. The first one added becomes the current year.
     */
    public function store(SchoolYearRequest $request, AuditLogger $audit): RedirectResponse
    {
        $schoolYear = SchoolYear::create($request->validated());

        if (SchoolYear::current() === null) {
            $schoolYear->markAsCurrent();
        }

        $audit->record(AuditAction::RecordCreated, 'school_years', $schoolYear, "SY {$schoolYear->name}");

        return back()->with('status', "School year {$schoolYear->name} added.");
    }

    /**
     * Correct a school year's name or dates.
     */
    public function update(SchoolYearRequest $request, SchoolYear $schoolYear, AuditLogger $audit): RedirectResponse
    {
        $schoolYear->fill($request->validated());

        if (! $schoolYear->isDirty()) {
            return back()->with('status', 'No changes to save.');
        }

        $changedFields = implode(', ', array_keys($schoolYear->getDirty()));
        $schoolYear->save();

        $audit->record(AuditAction::RecordUpdated, 'school_years', $schoolYear, "Changed: {$changedFields}");

        return back()->with('status', "School year {$schoolYear->name} saved.");
    }
}
