<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CurrentSchoolYearController extends Controller
{
    /**
     * Make a school year the current one. Exactly one year is current at a time.
     */
    public function __invoke(SchoolYear $schoolYear, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('update', $schoolYear);

        if (! $schoolYear->is_current) {
            $schoolYear->markAsCurrent();

            $audit->record(AuditAction::RecordUpdated, 'school_years', $schoolYear, "Set SY {$schoolYear->name} as the current school year");
        }

        return back()->with('status', "SY {$schoolYear->name} is now the current school year.");
    }
}
