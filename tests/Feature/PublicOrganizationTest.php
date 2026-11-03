<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Tests\TestCase;

class PublicOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_organization_page_shows_public_profile_and_only_published_campaigns(): void
    {
        $organization = Organization::create(['slug' => 'lembaga-peduli', 'name' => 'Lembaga Peduli', 'description' => 'Dampak bersama.', 'tagline' => 'Bersama berdampak.', 'primary_color' => '#AA0000', 'secondary_color' => '#FFF1F0', 'contact_email' => 'halo@peduli.test', 'status' => 'verified', 'verified_at' => now()]);
        Campaign::create(['organization_id' => $organization->id, 'title' => 'Program Published', 'slug' => 'program-published-'.Str::random(5), 'description' => 'Cerita', 'target_amount' => 1000000, 'status' => 'published', 'published_at' => now()]);
        Campaign::create(['organization_id' => $organization->id, 'title' => 'Program Draft', 'slug' => 'program-draft-'.Str::random(5), 'description' => 'Rahasia', 'target_amount' => 1000000, 'status' => 'draft']);

        $this->get(route('public.organizations.show', $organization->slug))->assertOk()->assertSee('Lembaga Peduli')->assertSee('Bersama berdampak.')->assertSee('Program Published')->assertDontSee('Program Draft')->assertSee('halo@peduli.test')->assertSee('--public-primary: #AA0000', false);
    }

    public function test_unverified_organization_is_not_publicly_accessible(): void
    {
        $organization = Organization::create(['slug' => 'lembaga-pending', 'name' => 'Lembaga Pending', 'status' => 'pending']);

        $this->get(route('public.organizations.show', $organization->slug))->assertNotFound();
    }

    public function test_public_organization_index_lists_verified_organizations(): void
    {
        $organization = Organization::create(['slug' => 'lembaga-index', 'name' => 'Lembaga Index', 'status' => 'verified', 'verified_at' => now()]);
        Campaign::create(['organization_id' => $organization->id, 'title' => 'Campaign Index', 'slug' => 'campaign-index', 'description' => 'Cerita', 'target_amount' => 1000000, 'status' => 'published', 'published_at' => now()]);

        $this->get(route('public.organizations.index'))
            ->assertOk()
            ->assertSee('Lembaga Index')
            ->assertSee(route('public.organizations.show', $organization->slug), false)
            ->assertSee('1 program publik');
    }
}
