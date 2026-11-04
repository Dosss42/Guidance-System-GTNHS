<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\StoreCounselingSessionRequest;
use App\Models\CounselingSession;
use App\Models\GuidanceCase;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CounselingSessionController extends Controller
{
    /**
     * Record a session on a case, and optionally schedule a follow-up.
     */
    public function store(StoreCounselingSessionRequest $request, GuidanceCase $case, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $case, $audit): void {
            $session = $case->sessions()->create($request->safe()->only(['session_at', 'session_type', 'confidential_notes', 'next_steps']));

            $audit->record(AuditAction::RecordCreated, 'sessions', $case, "Session #{$session->id} recorded (notes sealed)");

            if ($request->filled('follow_up_on')) {
                $followUp = $case->followUps()->create([
                    'due_on' => $request->validated('follow_up_on'),
                    'purpose' => $request->validated('follow_up_purpose'),
                ]);

                $audit->record(AuditAction::RecordCreated, 'follow_ups', $case, "Follow-up #{$followUp->id} due {$followUp->due_on->toDateString()}");
            }
        });

        return back()->with('status', 'Session recorded. Its notes are sealed.');
    }

    /**
     * Unseal a session's notes for the signed-in counselor. Every unseal
     * is recorded in the audit log under that counselor's name.
     */
    public function notes(CounselingSession $session, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewNotes', $session);

        $audit->record(AuditAction::NotesUnsealed, 'sessions', $session->guidanceCase, "Session #{$session->id} notes unsealed");

        return response()
            ->json([
                'notes' => $session->confidential_notes,
                'nextSteps' => $session->next_steps,
                'unsealedAt' => now()->toIso8601String(),
            ])
            ->header('Cache-Control', 'no-store, private');
    }
}
