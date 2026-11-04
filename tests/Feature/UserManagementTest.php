<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\RoleSlug;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = User::factory()->administrator()->create();
    }

    public function test_the_administrator_sees_every_account(): void
    {
        User::factory()->count(2)->counselor()->create();
        User::factory()->inactive()->create();

        $this->actingAs($this->administrator)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->has('users', 4)
                ->has('roles', 2)
                ->missing('users.0.password'));
    }

    public function test_the_administrator_creates_a_counselor_account(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('admin.users.store'), [
                'name' => 'Celia Navarro',
                'email' => 'c.navarro@gtnhs.test',
                'role' => RoleSlug::Counselor->value,
                'password' => 'Initial-Pass-2026',
                'password_confirmation' => 'Initial-Pass-2026',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $account = User::where('email', 'c.navarro@gtnhs.test')->sole();

        $this->assertTrue($account->isCounselor());
        $this->assertTrue($account->is_active);
        $this->assertTrue(Hash::check('Initial-Pass-2026', $account->password));

        $entry = AuditLog::where('action', AuditAction::AccountCreated)->sole();
        $this->assertSame($this->administrator->id, $entry->user_id);
        $this->assertSame('users', $entry->record_type);
        $this->assertSame($account->id, $entry->record_id);
        $this->assertStringNotContainsString('Initial-Pass-2026', (string) $entry->description);
    }

    public function test_account_creation_is_validated(): void
    {
        $existing = User::factory()->create(['email' => 'taken@gtnhs.test']);

        $this->actingAs($this->administrator)
            ->post(route('admin.users.store'), [
                'name' => '',
                'email' => $existing->email,
                'role' => 'teacher',
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors(['name', 'email', 'role', 'password']);

        $this->assertSame(2, User::count());
    }

    public function test_the_administrator_updates_an_account_and_can_reset_its_password(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->actingAs($this->administrator)
            ->patch(route('admin.users.update', $counselor), [
                'name' => 'Updated Name',
                'email' => $counselor->email,
                'role' => RoleSlug::Counselor->value,
                'password' => 'Reset-Pass-2026',
                'password_confirmation' => 'Reset-Pass-2026',
            ])
            ->assertRedirect();

        $counselor->refresh();
        $this->assertSame('Updated Name', $counselor->name);
        $this->assertTrue(Hash::check('Reset-Pass-2026', $counselor->password));

        $entry = AuditLog::where('action', AuditAction::AccountUpdated)->sole();
        $this->assertSame('Changed: name, password', $entry->description);
    }

    public function test_the_administrator_cannot_change_their_own_role(): void
    {
        $this->actingAs($this->administrator)
            ->patch(route('admin.users.update', $this->administrator), [
                'name' => $this->administrator->name,
                'email' => $this->administrator->email,
                'role' => RoleSlug::Counselor->value,
            ])
            ->assertSessionHasErrors('role');

        $this->assertTrue($this->administrator->fresh()->isAdministrator());
    }

    public function test_the_administrator_deactivates_and_reactivates_an_account(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->actingAs($this->administrator)
            ->patch(route('admin.users.status', $counselor), ['is_active' => false])
            ->assertRedirect();

        $this->assertFalse($counselor->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::AccountDeactivated->value, 'record_id' => $counselor->id]);

        $this->actingAs($this->administrator)
            ->patch(route('admin.users.status', $counselor), ['is_active' => true]);

        $this->assertTrue($counselor->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::AccountActivated->value, 'record_id' => $counselor->id]);
    }

    public function test_the_administrator_cannot_deactivate_their_own_account(): void
    {
        $this->actingAs($this->administrator)
            ->patch(route('admin.users.status', $this->administrator), ['is_active' => false])
            ->assertForbidden();

        $this->assertTrue($this->administrator->fresh()->is_active);
    }
}
