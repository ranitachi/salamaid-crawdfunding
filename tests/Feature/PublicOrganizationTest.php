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
        $organization = Organization::create(['slug' => 'lembaga-peduli', 'name' => 'Lembaga Peduli', 'description' => 'Dampak bersama.', 'contact_email' => 'halo@peduli.test', 'status' => 'verified', 'verified_at' => now()]);
        Campaign::create(['organization_id' => $organization->id, 'title' => 'Program Published', 'slug' => 'program-published-'.Str::random(5), 'description' => 'Cerita', 'target_amount' => 1000000, 'status' => 'published', 'published_at' => now()]);
        Campaign::create(['organization_id' => $organization->id, 'title' => 'Program Draft', 'slug' => 'program-draft-'.Str::random(5), 'description' => 'Rahasia', 'target_amount' => 1000000, 'status' => 'draft']);

        $this->get(route('public.organizations.show', $organization->slug))->assertOk()->assertSee('Lembaga Peduli')->assertSee('Program Published')->assertDontSee('Program Draft')->assertSee('halo@peduli.test');
    }

    public function test_unverified_organization_is_not_publicly_accessible(): void
    {
        $organization = Organization::create(['slug' => 'lembaga-pending', 'name' => 'Lembaga Pending', 'status' => 'pending']);

        $this->get(route('public.organizations.show', $organization->slug))->assertNotFound();
    }
}
