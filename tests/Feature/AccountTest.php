<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function addSessionFor(User $user, string $id): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.9',
            'user_agent' => 'Other device',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);
    }

    public function test_both_roles_can_open_their_account_page(): void
    {
        foreach ([User::factory()->counselor()->create(), User::factory()->administrator()->create()] as $user) {
            $this->actingAs($user)
                ->get(route('account.show'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component('account/index')->where('account.email', $user->email));
        }
    }

    public function test_changing_the_password_needs_the_current_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'not-my-password',
                'password' => 'New-Password-2026',
                'password_confirmation' => 'New-Password-2026',
            ])
            ->assertSessionHasErrors(['current_password' => "That isn't your current password."]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_a_weak_new_password_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_changing_the_password_signs_out_other_devices_and_is_logged(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $this->addSessionFor($user, 'other-device-session');

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'New-Password-2026',
                'password_confirmation' => 'New-Password-2026',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('New-Password-2026', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => AuditAction::PasswordChanged->value]);
    }

    public function test_deactivating_an_account_ends_its_sessions_immediately(): void
    {
        config(['session.driver' => 'database']);
        $counselor = User::factory()->counselor()->create();
        $this->addSessionFor($counselor, 'counselor-session');

        $this->actingAs(User::factory()->administrator()->create())
            ->patch(route('admin.users.status', $counselor), ['is_active' => false]);

        $this->assertDatabaseMissing('sessions', ['id' => 'counselor-session']);
    }

    public function test_an_administrator_password_reset_ends_the_users_sessions(): void
    {
        config(['session.driver' => 'database']);
        $counselor = User::factory()->counselor()->create();
        $this->addSessionFor($counselor, 'counselor-session');

        $this->actingAs(User::factory()->administrator()->create())
            ->patch(route('admin.users.update', $counselor), [
                'name' => $counselor->name,
                'email' => $counselor->email,
                'role' => 'guidance_counselor',
                'password' => 'Reset-Pass-2026',
                'password_confirmation' => 'Reset-Pass-2026',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'counselor-session']);
    }

    public function test_guests_cannot_open_the_account_page(): void
    {
        $this->get(route('account.show'))->assertRedirect(route('login'));
    }
}
