<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminSidebarPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function sidebar(TestResponse $response): string
    {
        $html = $response->getContent();
        $start = strpos($html, '<aside class="admin-sidebar"');
        $end = strpos($html, '</aside>', $start);

        return substr($html, $start, $end - $start);
    }

    public function test_reviewer_sees_only_review_workspaces(): void
    {
        $response = $this->actingAs(User::factory()->role('reviewer')->create())->get('/admin');

        $response->assertOk();
        $sidebar = $this->sidebar($response);
        $this->assertStringContainsString('href="/admin/campaigns"', $sidebar);
        $this->assertStringContainsString('href="/admin/organizations"', $sidebar);
        $this->assertStringNotContainsString('href="/admin/donations"', $sidebar);
        $this->assertStringNotContainsString('href="/admin/payments"', $sidebar);
        $this->assertStringNotContainsString('href="/admin/users"', $sidebar);
    }

    public function test_finance_sees_financial_workspaces_only(): void
    {
        $response = $this->actingAs(User::factory()->role('finance')->create())->get('/admin');

        $response->assertOk();
        $sidebar = $this->sidebar($response);
        $this->assertStringContainsString('href="/admin/donations"', $sidebar);
        $this->assertStringContainsString('href="/admin/payments"', $sidebar);
        $this->assertStringContainsString('href="/admin/payouts"', $sidebar);
        $this->assertStringNotContainsString('href="/admin/campaigns"', $sidebar);
        $this->assertStringNotContainsString('href="/admin/organizations"', $sidebar);
        $this->assertStringNotContainsString('href="/admin/users"', $sidebar);
    }

    public function test_super_admin_sees_user_and_settings_workspaces(): void
    {
        $response = $this->actingAs(User::factory()->role('super_admin')->create())->get('/admin');

        $response->assertOk();
        $sidebar = $this->sidebar($response);
        $this->assertStringContainsString('href="/admin/users"', $sidebar);
        $this->assertStringContainsString('href="/admin/settings"', $sidebar);
        $this->assertStringContainsString('href="/admin/audit-logs"', $sidebar);
    }
}
