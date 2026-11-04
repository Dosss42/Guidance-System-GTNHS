<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_sends_each_role_to_its_own_dashboard(): void
    {
        $this->actingAs(User::factory()->counselor()->create())
            ->get('/')
            ->assertRedirect(route('counselor.dashboard'));

        $this->actingAs(User::factory()->administrator()->create())
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_counselor_sees_the_counselor_desk(): void
    {
        $this->actingAs(User::factory()->counselor()->create())
            ->get(route('counselor.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('counselor/dashboard'));
    }

    public function test_a_counselor_cannot_open_any_administrator_page(): void
    {
        $counselor = User::factory()->counselor()->create();
        $otherAccount = User::factory()->create();

        $this->actingAs($counselor);

        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->post(route('admin.users.store'), [])->assertForbidden();
        $this->patch(route('admin.users.update', $otherAccount), [])->assertForbidden();
        $this->patch(route('admin.users.status', $otherAccount), ['is_active' => false])->assertForbidden();

        $this->assertTrue($otherAccount->fresh()->is_active);
    }

    public function test_an_administrator_cannot_open_the_counselor_desk(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->get(route('counselor.dashboard'))
            ->assertForbidden();
    }

    public function test_a_user_deactivated_mid_session_is_signed_out_on_the_next_request(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->actingAs($counselor);
        $counselor->update(['is_active' => false]);

        $this->get(route('counselor.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }
}
