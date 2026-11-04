<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Enums\FollowUpStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Counselor\Concerns\PresentsCasework;
use App\Http\Requests\Counselor\StoreFollowUpRequest;
use App\Http\Requests\Counselor\UpdateFollowUpRequest;
use App\Models\FollowUp;
use App\Models\GuidanceCase;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FollowUpController extends Controller
{
    use PresentsCasework;

    /**
     * Follow-ups across all cases. Pending ones (overdue first) by default.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FollowUp::class);

        $view = in_array($request->query('view'), ['pending', 'done'], true) ? $request->query('view') : 'pending';

        $followUps = FollowUp::with(['guidanceCase:id,reference,student_id', 'guidanceCase.student.currentEnrollment.section', 'creator:id,name', 'updater:id,name'])
            ->when($view === 'pending', fn ($query) => $query->pending()->orderBy('due_on'))
            ->when($view === 'done', fn ($query) => $query->where('status', '!=', FollowUpStatus::Pending)->orderByDesc('updated_at'))
            ->paginate(30)
            ->withQueryString()
            ->through(fn (FollowUp $followUp): array => [
                'id' => $followUp->id,
                'dueOn' => $followUp->due_on->toDateString(),
                'purpose' => $followUp->purpose,
                'status' => $followUp->status->value,
                'statusLabel' => $followUp->isOverdue() ? 'Overdue' : $followUp->status->label(),
                'isOverdue' => $followUp->isOverdue(),
                'outcome' => $followUp->outcome,
                'caseId' => $followUp->guidance_case_id,
                'caseReference' => $followUp->guidanceCase->reference,
                'student' => self::studentSummary($followUp->guidanceCase->student),
                ...self::signature($followUp),
            ]);

        return Inertia::render('counselor/follow-ups/index', [
            'followUps' => $followUps,
            'view' => $view,
            'today' => today()->toDateString(),
        ]);
    }

    public function store(StoreFollowUpRequest $request, GuidanceCase $case, AuditLogger $audit): RedirectResponse
    {
        $followUp = $case->followUps()->create($request->validated());

        $audit->record(AuditAction::RecordCreated, 'follow_ups', $case, "Follow-up #{$followUp->id} due {$followUp->due_on->toDateString()}");

        return back()->with('status', "Follow-up scheduled for {$followUp->due_on->format('M j, Y')}.");
    }

    /**
     * Complete, cancel or reschedule. Rescheduling keeps the old entry as
     * "Rescheduled" and creates a new pending follow-up, so history is kept.
     */
    public function update(UpdateFollowUpRequest $request, FollowUp $followUp, AuditLogger $audit): RedirectResponse
    {
        $status = FollowUpStatus::from($request->validated('status'));
        $case = $followUp->guidanceCase;

        DB::transaction(function () use ($request, $followUp, $status, $case, $audit): void {
            $followUp->update([
                'status' => $status,
                'outcome' => $request->validated('outcome') ?? $followUp->outcome,
                'completed_at' => $status === FollowUpStatus::Completed ? now() : null,
            ]);

            $audit->record(AuditAction::RecordUpdated, 'follow_ups', $case, "Follow-up #{$followUp->id}: {$status->label()}");

            if ($status === FollowUpStatus::Rescheduled) {
                $replacement = $case->followUps()->create([
                    'due_on' => $request->validated('due_on'),
                    'purpose' => $followUp->purpose,
                ]);

                $audit->record(AuditAction::RecordCreated, 'follow_ups', $case, "Follow-up #{$replacement->id} due {$replacement->due_on->toDateString()} (rescheduled)");
            }
        });

        return back()->with('status', match ($status) {
            FollowUpStatus::Completed => 'Follow-up completed.',
            FollowUpStatus::Rescheduled => 'Follow-up moved to the new date.',
            FollowUpStatus::Cancelled => 'Follow-up cancelled.',
            FollowUpStatus::Pending => 'Follow-up saved.',
        });
    }
}
