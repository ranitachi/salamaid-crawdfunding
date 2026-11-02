<?php

namespace Tests\Feature\Admin;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AppSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('salamaid.app_settings');
        $this->withoutVite();
    }

    public function test_super_admin_can_view_and_update_branding_settings(): void
    {
        $admin = User::factory()->role('super_admin')->create();

        $this->actingAs($admin)->get('/admin/settings')
            ->assertOk()
            ->assertSee('Pengaturan aplikasi');

        $this->actingAs($admin)->put('/admin/settings', [
            'app_name' => 'Salamaid Foundation',
            'app_tagline' => 'Jembatan Kebaikan',
            'primary_color' => '#B91C1C',
            'primary_soft_color' => '#FEE2E2',
            'accent_color' => '#FB7185',
            'brand_icon' => 'SF',
            'logo_url' => 'https://example.com/logo.svg',
            'favicon_url' => 'https://example.com/favicon.ico',
            'contact_email' => 'hello@example.com',
            'contact_phone' => '+62 811 0000 0000',
            'contact_address' => 'Jl. Kebaikan 1',
            'contact_city' => 'Jakarta',
            'contact_country' => 'Indonesia',
        ])->assertRedirect('/admin/settings');

        $this->assertDatabaseHas('app_settings', ['key' => 'app_name', 'value' => 'Salamaid Foundation']);
        $this->actingAs($admin)->get('/admin')->assertSee('Salamaid Foundation');
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'settings.branding.updated', 'actor_id' => $admin->id]);
    }

    public function test_non_super_admin_cannot_access_settings(): void
    {
        foreach (['admin', 'reviewer', 'finance', 'support'] as $role) {
            $this->actingAs(User::factory()->role($role)->create())->get('/admin/settings')->assertForbidden();
        }
    }

    public function test_invalid_colors_and_logo_urls_are_rejected(): void
    {
        $admin = User::factory()->role('super_admin')->create();

        $this->actingAs($admin)->put('/admin/settings', [
            'app_name' => 'Bad Settings',
            'primary_color' => 'red',
            'primary_soft_color' => '#FEE2E2',
            'accent_color' => '#FB7185',
            'brand_icon' => 'S',
            'logo_url' => 'not-a-url',
        ])->assertSessionHasErrors(['primary_color', 'logo_url']);

        $this->assertDatabaseMissing('app_settings', ['key' => 'app_name', 'value' => 'Bad Settings']);
    }
}
