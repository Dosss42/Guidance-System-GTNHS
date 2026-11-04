<?php

namespace Tests\Feature;

use App\Http\Middleware\AddSecurityHeaders;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_sends_protective_headers(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'same-origin')
            ->assertHeader('Content-Security-Policy', AddSecurityHeaders::CONTENT_SECURITY_POLICY);
    }

    public function test_signed_in_pages_are_never_cached(): void
    {
        $this->actingAs(User::factory()->counselor()->create())
            ->get(route('counselor.dashboard'))
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_hsts_is_sent_only_over_https_when_enabled(): void
    {
        config(['security.hsts' => true]);

        $this->get(route('login'))->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_signing_in_starts_a_new_session(): void
    {
        $counselor = User::factory()->create();

        $this->get(route('login'));
        $before = session()->getId();

        $this->post(route('login.store'), ['email' => $counselor->email, 'password' => 'password']);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_user_typed_text_is_sent_as_data_not_markup(): void
    {
        $counselor = User::factory()->counselor()->create();
        $referral = Referral::factory()->create(['reason' => '<script>alert("x")</script>']);

        $response = $this->actingAs($counselor)->get(route('counselor.referrals.show', $referral));

        $response->assertOk();
        $this->assertStringNotContainsString('<script>alert("x")</script>', $response->getContent());
    }

    public function test_report_exports_neutralise_spreadsheet_formulas(): void
    {
        $counselor = User::factory()->counselor()->create(['name' => '=HYPERLINK("http://evil")']);

        $csv = $this->actingAs($counselor)
            ->get(route('counselor.reports.export', ['year' => 'custom', 'from' => '2026-01-01', 'to' => '2026-01-31']))
            ->streamedContent();

        $this->assertStringNotContainsString(',"=HYPERLINK', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_the_session_cookie_is_http_only(): void
    {
        $this->assertTrue(config('session.http_only'));
    }
}
