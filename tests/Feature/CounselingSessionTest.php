<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\CounselingSession;
use App\Models\FollowUp;
use App\Models\GuidanceCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CounselingSessionTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET_NOTE = 'Synthetic note: student discussed the study plan.';

    private User $counselor;

    private GuidanceCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counselor = User::factory()->counselor()->create();
        $this->case = GuidanceCase::factory()->create();
    }

    private function recordSession(array $overrides = []): void
    {
        $this->actingAs($this->counselor)
            ->post(route('counselor.cases.sessions.store', $this->case), [
                'session_at' => now()->subHour()->format('Y-m-d\TH:i'),
                'session_type' => 'individual',
                'confidential_notes' => self::SECRET_NOTE,
                'next_steps' => 'Adviser check-in.',
                ...$overrides,
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_session_notes_are_encrypted_in_the_database(): void
    {
        $this->recordSession();

        $stored = DB::table('counseling_sessions')->value('confidential_notes');

        $this->assertNotSame(self::SECRET_NOTE, $stored);
        $this->assertStringNotContainsString('study plan', $stored);
        $this->assertSame(self::SECRET_NOTE, CounselingSession::sole()->confidential_notes);
    }

    public function test_the_case_page_never_includes_session_notes(): void
    {
        $this->recordSession();

        $response = $this->actingAs($this->counselor)->get(route('counselor.cases.show', $this->case));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('counselor/cases/show')
            ->where('history.0.kind', 'session'));
        $this->assertStringNotContainsString('study plan', $response->getContent());
        $this->assertStringNotContainsString('Adviser check-in', $response->getContent());
    }

    public function test_unsealing_returns_the_notes_and_is_logged(): void
    {
        $this->recordSession();
        $session = CounselingSession::sole();
        $colleague = User::factory()->counselor()->create();

        $this->actingAs($colleague)
            ->getJson(route('counselor.sessions.notes', $session))
            ->assertOk()
            ->assertJson(['notes' => self::SECRET_NOTE, 'nextSteps' => 'Adviser check-in.'])
            ->assertHeader('Cache-Control', 'no-store, private');

        $entry = AuditLog::where('action', AuditAction::NotesUnsealed)->sole();
        $this->assertSame($colleague->id, $entry->user_id);
        $this->assertStringNotContainsString('study plan', (string) $entry->description);
    }

    public function test_the_administrator_cannot_unseal_notes(): void
    {
        $session = CounselingSession::factory()->for($this->case)->create();

        $this->actingAs(User::factory()->administrator()->create())
            ->getJson(route('counselor.sessions.notes', $session))
            ->assertForbidden();

        $this->assertSame(0, AuditLog::where('action', AuditAction::NotesUnsealed)->count());
    }

    public function test_a_session_can_schedule_a_follow_up(): void
    {
        $this->recordSession([
            'follow_up_on' => today()->addWeek()->toDateString(),
            'follow_up_purpose' => 'Check-in with adviser',
        ]);

        $followUp = FollowUp::sole();
        $this->assertTrue($followUp->guidanceCase->is($this->case));
        $this->assertSame($this->counselor->id, $followUp->created_by);
    }

    public function test_sessions_cannot_be_recorded_in_the_future_or_on_a_closed_case(): void
    {
        $this->actingAs($this->counselor)
            ->post(route('counselor.cases.sessions.store', $this->case), [
                'session_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'session_type' => 'individual',
                'confidential_notes' => 'x',
            ])
            ->assertSessionHasErrors('session_at');

        $closed = GuidanceCase::factory()->closed()->create();

        $this->actingAs($this->counselor)
            ->post(route('counselor.cases.sessions.store', $closed), [
                'session_at' => now()->subHour()->format('Y-m-d\TH:i'),
                'session_type' => 'individual',
                'confidential_notes' => 'x',
            ])
            ->assertSessionHasErrors('session_at');

        $this->assertSame(0, CounselingSession::count());
    }
}
