<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\LaunchChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LaunchCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_administrator_is_created_from_the_command_line(): void
    {
        $this->artisan('guidance:create-admin', ['--name' => 'Ricardo Torres', '--email' => 'R.Torres@school.example'])
            ->expectsQuestion('Password (at least 10 characters, upper and lower case letters and a number)', 'First-Admin-2026')
            ->expectsQuestion('Type the password again', 'First-Admin-2026')
            ->assertSuccessful();

        $admin = User::sole();
        $this->assertSame('r.torres@school.example', $admin->email);
        $this->assertTrue($admin->isAdministrator());
        $this->assertTrue(Hash::check('First-Admin-2026', $admin->password));
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::AccountCreated->value, 'record_id' => $admin->id]);
    }

    public function test_a_weak_or_mismatched_password_creates_nothing(): void
    {
        $this->artisan('guidance:create-admin', ['--name' => 'Ricardo Torres', '--email' => 'admin@school.example'])
            ->expectsQuestion('Password (at least 10 characters, upper and lower case letters and a number)', 'weak')
            ->expectsQuestion('Type the password again', 'different')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_the_launch_check_fails_on_a_development_setup(): void
    {
        User::factory()->administrator()->create(['email' => 'admin@gtnhs.test']);

        $this->artisan('guidance:check')->assertFailed();

        $results = collect(app(LaunchChecklist::class)->run())->keyBy('check');
        $this->assertSame('fail', $results['Production environment']['result']);
        $this->assertSame('fail', $results['No sample accounts']['result']);
        $this->assertSame('pass', $results['An active System Administrator exists']['result']);
    }

    public function test_the_launch_check_reports_a_running_scheduler(): void
    {
        $results = collect(app(LaunchChecklist::class)->run())->keyBy('check');
        $this->assertSame('fail', $results['Scheduler running']['result']);

        Cache::put(LaunchChecklist::HEARTBEAT_KEY, now()->toIso8601String());

        $results = collect(app(LaunchChecklist::class)->run())->keyBy('check');
        $this->assertSame('pass', $results['Scheduler running']['result']);
    }

    public function test_a_production_ready_configuration_passes_its_settings_checks(): void
    {
        config([
            'app.debug' => false,
            'app.url' => 'https://guidance.school.example',
            'session.encrypt' => true,
            'session.secure' => true,
            'session.lifetime' => 30,
            'security.csp' => true,
            'security.hsts' => true,
        ]);

        $results = collect(app(LaunchChecklist::class)->run())->keyBy('check');

        foreach (['Debug mode off', 'Site uses HTTPS', 'Session data encrypted', 'Cookies sent over HTTPS only', 'Idle sign-out within 60 minutes', 'Content Security Policy on', 'HSTS on'] as $check) {
            $this->assertSame('pass', $results[$check]['result'], $check);
        }
    }
}
