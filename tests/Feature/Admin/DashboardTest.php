<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_dashboard_by_supported_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->get('/admin?period=7d')
            ->assertOk()
            ->assertSee('7 hari terakhir')
            ->assertSee('/admin/campaigns?status=published', false);
    }

    public function test_custom_period_requires_valid_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->get('/admin?period=custom')
            ->assertRedirect()
            ->assertSessionHasErrors(['date_from', 'date_to']);
    }

    public function test_dashboard_rejects_unknown_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->get('/admin?period=all-time')
            ->assertRedirect()
            ->assertSessionHasErrors('period');
    }
}
