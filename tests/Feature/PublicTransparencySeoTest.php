<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Transparency\Entities\CampaignUpdate;
use Tests\TestCase;

class PublicTransparencySeoTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(string $status = 'published', string $organizationStatus = 'verified'): Campaign
    {
        $organization = Organization::create([
            'slug' => 'seo-'.Str::random(8),
            'name' => 'SEO Organization',
            'status' => $organizationStatus,
            'verified_at' => $organizationStatus === 'verified' ? now() : null,
        ]);

        return Campaign::create([
            'organization_id' => $organization->id,
            'title' => 'Transparansi Campaign',
            'slug' => 'transparansi-'.Str::random(8),
            'description' => 'Cerita campaign yang aman untuk publik.',
            'target_amount' => 1_000_000,
            'status' => $status,
        ]);
    }

    private function update(Campaign $campaign, string $status, string $title, int $amount = 0): CampaignUpdate
    {
        $update = CampaignUpdate::create([
            'campaign_id' => $campaign->id,
            'type' => $amount > 0 ? 'fund_report' : 'update',
            'title' => $title,
            'content' => 'Isi update publik.',
            'amount_used' => $amount ?: null,
        ]);
        $update->forceFill(['status' => $status, 'published_at' => $status === 'published' ? now() : null])->save();

        return $update;
    }

    public function test_transparency_page_only_shows_published_updates_and_reports(): void
    {
        $campaign = $this->campaign();
        $this->update($campaign, 'published', 'Laporan pembangunan', 250000);
        $this->update($campaign, 'draft', 'Draft internal', 750000);

        $this->get(route('public.campaigns.transparency', $campaign->slug))
            ->assertOk()
            ->assertSee('Laporan pembangunan')
            ->assertSee('Rp 250.000')
            ->assertDontSee('Draft internal')
            ->assertSee('canonical')
            ->assertSee('og:type');
    }

    public function test_transparency_and_sitemap_hide_unpublished_or_unverified_campaigns(): void
    {
        $published = $this->campaign();
        $draft = $this->campaign('draft');
        $unverified = $this->campaign('published', 'pending');

        $this->get(route('public.campaigns.transparency', $draft->slug))->assertNotFound();
        $this->get(route('public.campaigns.transparency', $unverified->slug))->assertNotFound();

        $this->get(route('public.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('public.campaigns.show', $published->slug), false)
            ->assertSee(route('public.campaigns.transparency', $published->slug), false)
            ->assertDontSee($draft->slug)
            ->assertDontSee($unverified->slug);
    }

    public function test_robots_disallows_private_areas_and_points_to_sitemap(): void
    {
        $this->get(route('public.robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /account')
            ->assertSee('Sitemap: '.route('public.sitemap'));
    }
}
