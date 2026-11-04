<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\StoreEnrollmentRequest;
use App\Http\Requests\Counselor\UpdateEnrollmentRequest;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;

class StudentEnrollmentController extends Controller
{
    /**
     * Add a student's enrollment for another school year.
     */
    public function store(StoreEnrollmentRequest $request, Student $student, AuditLogger $audit): RedirectResponse
    {
        $enrollment = $student->enrollments()->create($request->validated());

        $audit->record(AuditAction::RecordCreated, 'enrollments', $student, StudentController::describeEnrollment($enrollment));

        return back()->with('status', "Enrollment for SY {$enrollment->schoolYear->name} added.");
    }

    /**
     * Correct an enrollment's section or status. The school year never changes.
     */
    public function update(UpdateEnrollmentRequest $request, Student $student, StudentEnrollment $enrollment, AuditLogger $audit): RedirectResponse
    {
        $enrollment->fill($request->validated());

        if (! $enrollment->isDirty()) {
            return back()->with('status', 'No changes to save.');
        }

        $enrollment->save();

        $audit->record(AuditAction::RecordUpdated, 'enrollments', $student, StudentController::describeEnrollment($enrollment->refresh()));

        return back()->with('status', "Enrollment for SY {$enrollment->schoolYear->name} updated.");
    }
}
