<?php

namespace Tests\Feature\Admin;

use App\Contracts\AdminAuditLogger;
use Illuminate\Support\Facades\DB;
use Mockery;
use Modules\Catalog\App\Queries\CampaignModerationQuery;

class CampaignModerationTest extends AdminReviewTestCase
{
    public function test_unverified_organization_campaign_cannot_be_published(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('pending');
        $campaign = $this->campaign($organization, 'under_review');
        $this->media($campaign);

        $response = $this->actingAs($reviewer)->post(route('admin.campaigns.publish', $campaign));

        $response->assertRedirect(route('admin.campaigns.show', $campaign))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'belum terverifikasi'));

        $campaign->refresh();

        $this->assertSame('under_review', $campaign->status);
        $this->assertNull($campaign->published_at);
    }

    public function test_reviewer_can_publish_campaign_for_verified_organization(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('verified');
        $campaign = $this->campaign($organization, 'under_review');
        $this->media($campaign);

        $response = $this->actingAs($reviewer)->post(route('admin.campaigns.publish', $campaign));

        $response->assertRedirect(route('admin.campaigns.show', $campaign))
            ->assertSessionHas('success');

        $campaign->refresh();

        $this->assertSame('published', $campaign->status);
        $this->assertNotNull($campaign->published_at);
        $this->assertSame($reviewer->id, $campaign->reviewed_by);
        $this->assertNotNull($campaign->reviewed_at);
    }

    public function test_support_can_view_but_cannot_moderate_campaign(): void
    {
        $support = $this->user('support');
        $organization = $this->organization('verified');
        $campaign = $this->campaign($organization, 'under_review');
        $this->media($campaign);

        $this->actingAs($support)
            ->get(route('admin.campaigns.index'))
            ->assertOk();

        $this->actingAs($support)
            ->get(route('admin.campaigns.show', $campaign))
            ->assertOk()
            ->assertViewHas('canModerate', false);

        $this->actingAs($support)
            ->post(route('admin.campaigns.publish', $campaign))
            ->assertForbidden();

        $this->assertSame('under_review', $campaign->fresh()->status);
    }

    public function test_support_cannot_reject_or_suspend_campaign(): void
    {
        $support = $this->user('support');
        $organization = $this->organization('verified');
        $campaign = $this->campaign($organization, 'under_review');
        $this->media($campaign);

        $this->actingAs($support)
            ->post(route('admin.campaigns.reject', $campaign), ['reason' => 'Konten belum sesuai.'])
            ->assertForbidden();

        $this->actingAs($support)
            ->post(route('admin.campaigns.suspend', $campaign), ['reason' => 'Konten belum sesuai.'])
            ->assertForbidden();

        $this->assertSame('under_review', $campaign->fresh()->status);
    }

    public function test_finance_without_campaign_permission_is_denied(): void
    {
        $finance = $this->user('finance');
        $organization = $this->organization('verified');
        $campaign = $this->campaign($organization, 'under_review');

        $this->actingAs($finance)
            ->get(route('admin.campaigns.index'))
            ->assertForbidden();

        $this->actingAs($finance)
            ->post(route('admin.campaigns.publish', $campaign))
            ->assertForbidden();
    }

    public function test_reject_campaign_requires_reason(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('verified');
        $campaign = $this->campaign($organization, 'under_review');

        $response = $this->actingAs($reviewer)->post(route('admin.campaigns.reject', $campaign), []);

        $response->assertSessionHasErrors('reason');
        $this->assertSame('under_review', $campaign->fresh()->status);

        $response = $this->actingAs($reviewer)->post(route('admin.campaigns.reject', $campaign), [
            'reason' => 'Deskripsi belum menjelaskan rencana penggunaan dana.',
        ]);

        $response->assertRedirect(route('admin.campaigns.show', $campaign))
            ->assertSessionHas('success');

        $campaign->refresh();

        $this->assertSame('rejected', $campaign->status);
        $this->assertSame(
            'Deskripsi belum menjelaskan rencana penggunaan dana.',
            $campaign->rejection_reason,
        );
    }

    public function test_published_campaign_cannot_be_deleted_permanently_or_soft_deleted(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('verified');
        $campaign = $this->campaign($organization, 'published', [
            'published_at' => now(),
        ]);

        $response = $this->actingAs($reviewer)
            ->delete(route('admin.campaigns.force-destroy', $campaign));

        $response->assertRedirect(route('admin.campaigns.show', $campaign))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'status' => 'published']);
        $this->assertNotSoftDeleted('campaigns', ['id' => $campaign->id]);

        $response = $this->actingAs($reviewer)
            ->delete(route('admin.campaigns.destroy', $campaign));

        $response->assertRedirect(route('admin.campaigns.show', $campaign))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('campaigns', ['id' => $campaign->id]);
    }

    public function test_illegal_campaign_status_transition_is_rejected(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('verified');

        $draft = $this->campaign($organization, 'draft');
        $this->media($draft);

        $response = $this->actingAs($reviewer)->post(route('admin.campaigns.publish', $draft));

        $response->assertRedirect(route('admin.campaigns.show', $draft))
            ->assertSessionHas('error');

        $this->assertSame('draft', $draft->fresh()->status);

        $published = $this->campaign($organization, 'published', ['published_at' => now()]);

        $response = $this->actingAs($reviewer)->post(route('admin.campaigns.publish', $published));

        $response->assertRedirect(route('admin.campaigns.show', $published))
            ->assertSessionHas('error');

        $this->assertSame('published', $published->fresh()->status);
    }

    public function test_campaign_can_be_featured_unfeatured_and_closed(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('verified');
        $campaign = $this->campaign($organization, 'published', ['published_at' => now()]);

        $this->actingAs($reviewer)
            ->post(route('admin.campaigns.feature', $campaign))
            ->assertSessionHas('success');

        $campaign->refresh();
        $this->assertTrue($campaign->is_featured);
        $this->assertNotNull($campaign->featured_at);

        $this->actingAs($reviewer)
            ->post(route('admin.campaigns.unfeature', $campaign))
            ->assertSessionHas('success');

        $campaign->refresh();
        $this->assertFalse($campaign->is_featured);
        $this->assertNull($campaign->featured_at);

        $this->actingAs($reviewer)
            ->post(route('admin.campaigns.close', $campaign))
            ->assertSessionHas('success');

        $campaign->refresh();
        $this->assertSame('closed', $campaign->status);
        $this->assertNotNull($campaign->closed_at);
    }

    public function test_campaign_index_filters_by_status_category_organization_and_featured(): void
    {
        $reviewer = $this->user('reviewer');
        $category = $this->category();
        $organization = $this->organization('verified');
        $otherOrganization = $this->organization('verified');

        $matching = $this->campaign($organization, 'published', [
            'category_id' => $category->id,
            'is_featured' => true,
            'published_at' => now(),
            'title' => 'Campaign Yang Cocok',
        ]);

        $this->campaign($organization, 'draft', [
            'category_id' => $category->id,
            'title' => 'Campaign Draft Tidak Cocok',
        ]);

        $this->campaign($otherOrganization, 'published', [
            'category_id' => $category->id,
            'is_featured' => true,
            'title' => 'Campaign Organisasi Lain',
        ]);

        $response = $this->actingAs($reviewer)->get(route('admin.campaigns.index', [
            'status' => 'published',
            'category_id' => $category->id,
            'organization_id' => $organization->id,
            'featured' => 1,
        ]));

        $response->assertOk()
            ->assertSee('Campaign Yang Cocok')
            ->assertDontSee('Campaign Draft Tidak Cocok')
            ->assertDontSee('Campaign Organisasi Lain')
            ->assertViewHas('campaigns', fn ($paginator) => $paginator->total() === 1);

        $this->assertDatabaseHas('campaigns', ['id' => $matching->id]);
    }

    public function test_campaign_detail_shows_content_media_and_moderation_state(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('verified');
        $campaign = $this->campaign($organization, 'published', [
            'published_at' => now(),
        ]);
        $media = $this->media($campaign);

        $response = $this->actingAs($reviewer)->get(route('admin.campaigns.show', $campaign));

        $response->assertOk()
            ->assertSee($campaign->title)
            ->assertSee($media->url)
            ->assertSee($organization->name)
            ->assertViewHas('canModerate', true);
    }

    public function test_campaign_list_query_uses_eager_loading_without_obvious_n_plus_one(): void
    {
        $organization = $this->organization('verified');
        $category = $this->category();

        for ($i = 0; $i < 5; $i++) {
            $this->campaign($organization, 'published', [
                'category_id' => $category->id,
                'published_at' => now(),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $paginator = app(CampaignModerationQuery::class)->paginate([], 15);

        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertSame(5, $paginator->total());
        $this->assertLessThanOrEqual(5, $queryCount, 'Query list campaign seharusnya memakai eager loading, bukan N+1.');
    }

    public function test_publish_action_records_audit_event(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('verified');
        $campaign = $this->campaign($organization, 'under_review');
        $this->media($campaign);

        $logger = Mockery::mock(AdminAuditLogger::class);
        $logger->shouldReceive('log')
            ->once()
            ->withArgs(function (
                string $action,
                $subject,
                array $before,
                array $after,
                ?string $reason,
                $actor,
                array $context,
            ) use ($campaign, $reviewer) {
                return $action === 'campaign.publish'
                    && $subject->getKey() === $campaign->getKey()
                    && $before['status'] === 'under_review'
                    && $after['status'] === 'published'
                    && $actor?->getKey() === $reviewer->getKey()
                    && $context['entity'] === 'campaign';
            });

        $this->app->instance(AdminAuditLogger::class, $logger);

        $this->actingAs($reviewer)
            ->post(route('admin.campaigns.publish', $campaign))
            ->assertSessionHas('success');
    }
}
