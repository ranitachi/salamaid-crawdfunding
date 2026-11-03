<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicAboutContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_links_open_database_backed_about_and_contact_pages(): void
    {
        Cache::forget('salamaid.app_settings');
        $admin = User::factory()->role('super_admin')->create();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'app_name' => 'Salamaid Demo',
            'app_tagline' => 'Admin Console',
            'public_tagline' => 'Kebaikan yang terhubung',
            'primary_color' => '#D92D20',
            'primary_soft_color' => '#FEE4E2',
            'accent_color' => '#FF806F',
            'brand_icon' => 'S',
            'contact_email' => 'halo@salamaid.test',
            'contact_phone' => '+62 811 2222 3333',
            'contact_address' => 'Jl. Kebaikan No. 1',
            'contact_city' => 'Jakarta',
            'contact_country' => 'Indonesia',
            'about_title' => 'Tentang Salamaid Demo',
            'about_content' => 'Kami menghubungkan kebaikan dengan dampak yang terukur.',
            'contact_description' => 'Tim kami siap membantu Anda.',
        ])->assertRedirect(route('admin.settings.index'));

        auth()->logout();
        $this->get(route('public.user'))
            ->assertOk()
            ->assertSee(route('public.about'), false)
            ->assertSee(route('public.contact'), false);

        $this->get(route('public.about'))
            ->assertOk()
            ->assertSee('Tentang Salamaid Demo')
            ->assertSee('Kami menghubungkan kebaikan dengan dampak yang terukur.');

        $this->get(route('public.contact'))
            ->assertOk()
            ->assertSee('halo@salamaid.test')
            ->assertSee('+62 811 2222 3333')
            ->assertSee('Jl. Kebaikan No. 1');
    }
}
