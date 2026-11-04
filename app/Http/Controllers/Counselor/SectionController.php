<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\SectionRequest;
use App\Models\Section;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;

class SectionController extends Controller
{
    /**
     * Add a section to a grade level.
     */
    public function store(SectionRequest $request, AuditLogger $audit): RedirectResponse
    {
        $section = Section::create($request->validated());

        $audit->record(AuditAction::RecordCreated, 'sections', $section, $section->label());

        return back()->with('status', "{$section->label()} added.");
    }

    /**
     * Rename a section or move it to another grade level.
     */
    public function update(SectionRequest $request, Section $section, AuditLogger $audit): RedirectResponse
    {
        $section->fill($request->validated());

        if (! $section->isDirty()) {
            return back()->with('status', 'No changes to save.');
        }

        $changedFields = implode(', ', array_keys($section->getDirty()));
        $section->save();

        $audit->record(AuditAction::RecordUpdated, 'sections', $section, "Changed: {$changedFields}");

        return back()->with('status', "{$section->label()} saved.");
    }
}
