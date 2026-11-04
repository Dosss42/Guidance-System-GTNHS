<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_entries_cannot_be_modified(): void
    {
        $entry = AuditLog::factory()->create();

        $this->expectException(LogicException::class);

        $entry->update(['action' => AuditAction::Logout]);
    }

    public function test_audit_entries_cannot_be_deleted(): void
    {
        $entry = AuditLog::factory()->create();

        try {
            $entry->delete();
            $this->fail('Deleting an audit entry should throw.');
        } catch (LogicException) {
            $this->assertModelExists($entry);
        }
    }

    public function test_the_administrator_can_filter_the_audit_log(): void
    {
        $administrator = User::factory()->administrator()->create();
        AuditLog::factory()->create(['action' => AuditAction::Login]);
        AuditLog::factory()->create(['action' => AuditAction::LoginFailed]);
        AuditLog::factory()->create(['action' => AuditAction::AccountCreated, 'module' => 'accounts']);

        $this->actingAs($administrator)
            ->get(route('admin.audit-logs.index', ['event' => 'sign-in']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/audit-logs/index')
                ->where('filter', 'sign-in')
                ->has('logs.data', 2));

        $this->actingAs($administrator)
            ->get(route('admin.audit-logs.index', ['event' => 'accounts']))
            ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1));
    }

    public function test_an_unknown_filter_falls_back_to_all_events(): void
    {
        $administrator = User::factory()->administrator()->create();
        AuditLog::factory()->count(3)->create();

        $this->actingAs($administrator)
            ->get(route('admin.audit-logs.index', ['event' => 'drop table']))
            ->assertInertia(fn (Assert $page) => $page->where('filter', 'all')->has('logs.data', 3));
    }

    public function test_the_admin_overview_counts_accounts_and_failed_sign_ins(): void
    {
        $administrator = User::factory()->administrator()->create();
        User::factory()->count(2)->counselor()->create();
        User::factory()->inactive()->create();
        AuditLog::factory()->for($administrator)->create(['action' => AuditAction::LoginFailed]);
        AuditLog::factory()->for($administrator)->create(['action' => AuditAction::LoginFailed, 'created_at' => now()->subDays(2)]);

        $this->actingAs($administrator)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->where('stats.activeCounselors', 2)
                ->where('stats.activeAdministrators', 1)
                ->where('stats.deactivatedAccounts', 1)
                ->where('stats.failedSignIns', 1));
    }
}
