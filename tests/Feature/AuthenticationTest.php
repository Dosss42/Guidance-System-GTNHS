<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sign_in_page_renders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('counselor.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_counselor_signs_in_to_the_counselor_desk(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->post(route('login.store'), ['email' => $counselor->email, 'password' => 'password'])
            ->assertRedirect(route('counselor.dashboard'));

        $this->assertAuthenticatedAs($counselor);
        $this->assertNotNull($counselor->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $counselor->id, 'action' => AuditAction::Login->value]);
    }

    public function test_an_administrator_signs_in_to_the_admin_overview(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->post(route('login.store'), ['email' => $administrator->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($administrator);
    }

    public function test_a_wrong_password_is_rejected_and_logged_without_the_password(): void
    {
        $counselor = User::factory()->create();

        $this->post(route('login.store'), ['email' => $counselor->email, 'password' => 'wrong-secret-value'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        $entry = AuditLog::sole();
        $this->assertSame(AuditAction::LoginFailed, $entry->action);
        $this->assertSame($counselor->id, $entry->user_id);
        $this->assertStringNotContainsString('wrong-secret-value', (string) $entry->description);
    }

    public function test_a_deactivated_account_cannot_sign_in(): void
    {
        $counselor = User::factory()->inactive()->create();

        $this->post(route('login.store'), ['email' => $counselor->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'This account has been deactivated. Ask the System Administrator for help.']);

        $this->assertGuest();
    }

    public function test_sign_in_pauses_after_too_many_failed_attempts(): void
    {
        $counselor = User::factory()->create();

        for ($attempt = 0; $attempt < LoginRequest::MAX_ATTEMPTS; $attempt++) {
            $this->post(route('login.store'), ['email' => $counselor->email, 'password' => 'wrong']);
        }

        $this->post(route('login.store'), ['email' => $counselor->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString('Too many sign-in attempts', session('errors')->first('email'));
    }

    public function test_signing_out_ends_the_session_and_is_logged(): void
    {
        $counselor = User::factory()->create();

        $this->actingAs($counselor)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['user_id' => $counselor->id, 'action' => AuditAction::Logout->value]);
    }

    public function test_signed_in_users_visiting_sign_in_go_to_their_dashboard(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
