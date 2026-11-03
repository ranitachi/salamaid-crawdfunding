<?php

namespace Tests\Feature\Admin;

use App\Contracts\AdminAuditLogger;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Core\Entities\OrganizationMember;
use Modules\Operations\Entities\Payout;
use Modules\Transparency\App\Events\CampaignUpdateStatusChanged;
use Modules\Transparency\Entities\CampaignUpdate;
use Modules\Transparency\Entities\CampaignUpdateAttachment;

class TransparencyManagementTest extends AdminReviewTestCase
{
    /** @var array<int, array{action: string, before: array, after: array, reason: ?string, actor: ?int}> */
    private array $audits = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');

        $audits = &$this->audits;
        $this->app->instance(AdminAuditLogger::class, new class($audits) implements AdminAuditLogger {
            public function __construct(private array &$audits)
            {
            }

            public function log(string $action, Model $subject, array $before, array $after, ?string $reason = null, ?User $actor = null, array $context = []): void
            {
                $this->audits[] = compact('action', 'before', 'after', 'reason') + ['actor' => $actor?->getKey(), 'context' => $context];
            }
        });
    }

    private function publishedCampaign(?Organization $organization = null): Campaign
    {
        return $this->campaign($organization ?? $this->organization('verified'), 'published');
    }

    private function update(Campaign $campaign, string $status = 'draft', string $type = 'update', array $attributes = []): CampaignUpdate
    {
        $update = new CampaignUpdate(array_merge([
            'campaign_id' => $campaign->id,
            'type' => $type,
            'title' => 'Laporan penyaluran tahap 1',
            'content' => 'Dana telah disalurkan untuk pembelian perlengkapan sekolah.',
            'amount_used' => $type === 'fund_report' ? 500_000 : null,
        ], $attributes));
        $update->forceFill(['status' => $status])->save();

        return $update;
    }

    private function attachment(CampaignUpdate $update): CampaignUpdateAttachment
    {
        $path = UploadedFile::fake()->create('bukti.pdf', 20, 'application/pdf')->store('transparency/evidence/'.$update->id, 'local');
        $attachment = new CampaignUpdateAttachment;
        $attachment->forceFill([
            'campaign_update_id' => $update->id, 'disk' => 'local', 'path' => $path,
            'original_name' => 'bukti.pdf', 'mime_type' => 'application/pdf', 'size' => 20480,
            'checksum' => str_repeat('a', 64),
        ])->save();

        return $attachment;
    }

    private function payout(Organization $organization, string $status = 'paid', int $amount = 1_000_000): Payout
    {
        return Payout::create([
            'organization_id' => $organization->id,
            'amount' => $amount,
            'status' => $status,
            'requested_by' => $this->user('finance')->id,
        ]);
    }

    // ---- Access control -------------------------------------------------

    public function test_guest_is_redirected_and_non_transparency_roles_are_forbidden(): void
    {
        $update = $this->update($this->publishedCampaign());

        $this->get('/admin/transparency')->assertRedirect(route('admin.login'));

        foreach (['user', 'finance', 'support'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)->get('/admin/transparency')->assertForbidden();
            $this->actingAs($user)->get("/admin/transparency/{$update->id}")->assertForbidden();
            $this->actingAs($user)->post("/admin/transparency/{$update->id}/submit")->assertForbidden();
            $this->actingAs($user)->post('/admin/transparency', [])->assertForbidden();
        }

        // EnsureUserIsAdmin: inactive users are logged out; non-dashboard admin routes answer 403.
        $this->actingAs($this->user('reviewer', active: false))->get('/admin/transparency')->assertForbidden();
        $this->assertGuest();
        $this->assertSame('draft', $update->fresh()->status);
    }

    public function test_reviewer_and_admin_can_list_filter_and_view(): void
    {
        $campaign = $this->publishedCampaign();
        $pending = $this->update($campaign, 'under_review', attributes: ['title' => 'Update menunggu review']);
        $this->update($campaign, 'published', attributes: ['title' => 'Update sudah tayang']);

        foreach (['reviewer', 'admin', 'super_admin'] as $role) {
            $this->actingAs($this->user($role))
                ->get('/admin/transparency?status=under_review')
                ->assertOk()
                ->assertSee('Update menunggu review')
                ->assertDontSee('Update sudah tayang');
        }

        $this->actingAs($this->user('reviewer'))
            ->get("/admin/transparency/{$pending->id}")
            ->assertOk()
            ->assertSee($campaign->title);

        $this->actingAs($this->user('reviewer'))->get('/admin/transparency?status=bogus')->assertSessionHasErrors('status');
    }

    public function test_organization_admin_can_submit_only_updates_for_owned_campaigns(): void
    {
        $organization = $this->organization('verified');
        $foreignOrganization = $this->organization('verified');
        $member = $this->user('organization_admin');
        OrganizationMember::create(['organization_id' => $organization->id, 'user_id' => $member->id, 'role' => 'owner']);
        $campaign = $this->publishedCampaign($organization);
        $foreignCampaign = $this->publishedCampaign($foreignOrganization);

        $this->actingAs($member)->get('/admin/transparency')->assertOk();
        $this->actingAs($member)->post('/admin/transparency', [
            'campaign_id' => $campaign->id,
            'type' => 'update',
            'title' => 'Kabar kegiatan lembaga',
            'content' => 'Kegiatan terbaru lembaga telah selesai dilaksanakan.',
        ])->assertRedirect();

        $update = CampaignUpdate::query()->where('campaign_id', $campaign->id)->firstOrFail();
        $this->actingAs($member)->post("/admin/transparency/{$update->id}/submit")->assertSessionHas('success');
        $this->actingAs($member)->post("/admin/transparency/{$update->id}/approve")->assertForbidden();
        $this->actingAs($member)->post("/admin/transparency/{$update->id}/publish")->assertForbidden();

        $foreignUpdate = $this->update($foreignCampaign);
        $this->actingAs($member)->get("/admin/transparency/{$foreignUpdate->id}")->assertForbidden();
        $this->actingAs($member)->post('/admin/transparency', [
            'campaign_id' => $foreignCampaign->id,
            'type' => 'update',
            'title' => 'Tidak boleh',
            'content' => 'Update untuk campaign milik lembaga lain.',
        ])->assertForbidden();
    }

    public function test_list_query_does_not_n_plus_one(): void
    {
        $reviewer = $this->user('reviewer');
        for ($i = 0; $i < 6; $i++) {
            $this->attachment($this->update($this->publishedCampaign()));
        }

        DB::enableQueryLog();
        $this->actingAs($reviewer)->get('/admin/transparency')->assertOk();
        $count = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'campaign'))->count();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(6, $count, 'Daftar transparansi harus memakai eager loading.');
    }

    // ---- Create & relation validation ----------------------------------

    public function test_reviewer_creates_draft_fund_report_linked_to_paid_payout(): void
    {
        $organization = $this->organization('verified');
        $campaign = $this->publishedCampaign($organization);
        $payout = $this->payout($organization);
        $reviewer = $this->user('reviewer');

        $this->actingAs($reviewer)->post('/admin/transparency', [
            'campaign_id' => $campaign->id,
            'type' => 'fund_report',
            'title' => 'Laporan pembelian buku',
            'content' => 'Pembelian 200 buku pelajaran untuk sekolah binaan.',
            'payout_id' => $payout->id,
            'amount_used' => 750_000,
            'status' => 'published', // must be ignored
        ])->assertRedirect();

        $update = CampaignUpdate::firstOrFail();
        $this->assertSame('draft', $update->status);
        $this->assertSame($payout->id, $update->payout_id);
        $this->assertSame($reviewer->id, (int) $update->created_by);
        $this->assertNull($update->published_at);
        $this->assertSame('transparency.create', $this->audits[0]['action']);
    }

    public function test_create_rejects_invalid_relations(): void
    {
        $organization = $this->organization('verified');
        $campaign = $this->publishedCampaign($organization);
        $reviewer = $this->user('reviewer');
        $base = ['campaign_id' => $campaign->id, 'type' => 'fund_report', 'title' => 'Laporan dana', 'content' => 'Isi laporan penggunaan dana yang cukup panjang.'];

        // Payout of another organization (IDOR across organizations).
        $foreign = $this->payout($this->organization('verified'));
        $this->actingAs($reviewer)->post('/admin/transparency', $base + ['payout_id' => $foreign->id, 'amount_used' => 1000])
            ->assertSessionHas('error');

        // Payout not yet paid.
        $requested = $this->payout($organization, 'requested');
        $this->actingAs($reviewer)->post('/admin/transparency', $base + ['payout_id' => $requested->id, 'amount_used' => 1000])
            ->assertSessionHas('error');

        // Amount larger than the payout.
        $paid = $this->payout($organization, 'paid', 1000);
        $this->actingAs($reviewer)->post('/admin/transparency', $base + ['payout_id' => $paid->id, 'amount_used' => 5000])
            ->assertSessionHas('error');

        // Field rules.
        $this->actingAs($reviewer)->post('/admin/transparency', $base)->assertSessionHasErrors('amount_used');
        $this->actingAs($reviewer)->post('/admin/transparency', ['type' => 'update', 'payout_id' => $paid->id, 'amount_used' => 1] + $base)
            ->assertSessionHasErrors(['payout_id', 'amount_used']);
        $this->actingAs($reviewer)->post('/admin/transparency', ['campaign_id' => '00000000-0000-0000-0000-000000000000'] + $base + ['amount_used' => 1])
            ->assertSessionHasErrors('campaign_id');

        $this->assertSame(0, CampaignUpdate::count());
    }

    // ---- Workflow --------------------------------------------------------

    public function test_full_review_and_publication_workflow_is_audited(): void
    {
        Event::fake([CampaignUpdateStatusChanged::class]);
        $reviewer = $this->user('reviewer');
        $update = $this->update($this->publishedCampaign(), 'draft', 'fund_report');

        $this->actingAs($reviewer)->post("/admin/transparency/{$update->id}/submit")->assertSessionHas('error');
        $this->assertSame('draft', $update->fresh()->status);

        $this->attachment($update);
        $this->actingAs($reviewer)->post("/admin/transparency/{$update->id}/submit")->assertSessionHas('success');
        $this->actingAs($reviewer)->post("/admin/transparency/{$update->id}/approve")->assertSessionHas('success');
        $this->assertSame(0, CampaignUpdate::query()->public()->count(), 'Approved update is not public yet.');

        $this->actingAs($reviewer)->post("/admin/transparency/{$update->id}/publish")->assertSessionHas('success');
        $this->assertSame(1, CampaignUpdate::query()->public()->count());
        $this->assertNotNull($update->fresh()->published_at);

        $this->actingAs($reviewer)->post("/admin/transparency/{$update->id}/unpublish", ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($reviewer)->post("/admin/transparency/{$update->id}/unpublish", ['reason' => 'Ada data penerima yang belum disamarkan.'])
            ->assertSessionHas('success');
        $this->assertSame('unpublished', $update->fresh()->status);
        $this->assertSame(0, CampaignUpdate::query()->public()->count());

        $this->assertSame(
            ['transparency.submit', 'transparency.approve', 'transparency.publish', 'transparency.unpublish'],
            array_column($this->audits, 'action'),
        );
        $publish = $this->audits[2];
        $this->assertSame(['approved', 'published'], [$publish['before']['status'], $publish['after']['status']]);
        $this->assertSame($reviewer->id, $publish['actor']);
        $this->assertSame('Ada data penerima yang belum disamarkan.', $this->audits[3]['reason']);

        Event::assertDispatched(CampaignUpdateStatusChanged::class, 4);
        Event::assertDispatched(CampaignUpdateStatusChanged::class, fn ($e) => $e->from === 'approved' && $e->to === 'published');
    }

    public function test_request_revision_requires_reason_and_allows_resubmission(): void
    {
        $reviewer = $this->user('reviewer');
        $update = $this->update($this->publishedCampaign(), 'under_review');

        $this->actingAs($reviewer)->post("/admin/transparency/{$update->id}/reject", ['reason' => 'abc'])->assertSessionHasErrors('reason');
        $this->assertSame('under_review', $update->fresh()->status);

        $this->actingAs($reviewer)->post("/admin/transparency/{$update->id}/reject", ['reason' => 'Foto bukti kurang jelas, mohon unggah ulang.'])
            ->assertSessionHas('success');
        $this->assertSame('rejected', $update->fresh()->status);
        $this->assertSame('Foto bukti kurang jelas, mohon unggah ulang.', $update->fresh()->review_note);

        $this->actingAs($reviewer)->post("/admin/transparency/{$update->id}/submit")->assertSessionHas('success');
        $this->assertSame('under_review', $update->fresh()->status);
        $this->assertNull($update->fresh()->review_note);
    }

    public function test_illegal_transitions_are_rejected(): void
    {
        $reviewer = $this->user('reviewer');
        $campaign = $this->publishedCampaign();
        $draft = $this->update($campaign, 'draft');
        $review = $this->update($campaign, 'under_review');

        $this->actingAs($reviewer)->post("/admin/transparency/{$draft->id}/approve")->assertSessionHas('error');
        $this->actingAs($reviewer)->post("/admin/transparency/{$draft->id}/publish")->assertSessionHas('error');
        $this->actingAs($reviewer)->post("/admin/transparency/{$review->id}/publish")->assertSessionHas('error');
        $this->actingAs($reviewer)->post("/admin/transparency/{$review->id}/unpublish", ['reason' => 'Alasan yang valid'])->assertSessionHas('error');

        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame('under_review', $review->fresh()->status);
        $this->assertSame([], $this->audits);
    }

    public function test_update_cannot_be_published_for_non_public_campaign(): void
    {
        $campaign = $this->campaign($this->organization('verified'), 'suspended');
        $update = $this->update($campaign, 'approved');

        $this->actingAs($this->user('reviewer'))->post("/admin/transparency/{$update->id}/publish")->assertSessionHas('error');
        $this->assertSame('approved', $update->fresh()->status);
    }

    // ---- Evidence files --------------------------------------------------

    public function test_evidence_upload_uses_private_storage_and_records_metadata(): void
    {
        $update = $this->update($this->publishedCampaign(), 'draft', 'fund_report');
        $file = UploadedFile::fake()->create('../../nota <script>.pdf', 120, 'application/pdf');

        $this->actingAs($this->user('reviewer'))
            ->post("/admin/transparency/{$update->id}/attachments", ['file' => $file])
            ->assertSessionHas('success');

        $attachment = CampaignUpdateAttachment::firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertStringStartsWith("transparency/evidence/{$update->id}/", $attachment->path);
        $this->assertStringNotContainsString('nota', $attachment->path, 'Stored name must be generated, not client supplied.');
        $this->assertSame('nota _script_.pdf', $attachment->original_name);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertSame(64, strlen($attachment->checksum));
        $this->assertFileDoesNotExist(public_path($attachment->path));
        $this->assertSame('transparency.attachment.upload', end($this->audits)['action']);
    }

    public function test_evidence_upload_validates_type_size_and_status(): void
    {
        $reviewer = $this->user('reviewer');
        $update = $this->update($this->publishedCampaign(), 'draft');

        $this->actingAs($reviewer)->post("/admin/transparency/{$update->id}/attachments", [
            'file' => UploadedFile::fake()->create('shell.php', 5, 'application/x-php'),
        ])->assertSessionHasErrors('file');

        $this->actingAs($reviewer)->post("/admin/transparency/{$update->id}/attachments", [
            'file' => UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $published = $this->update($this->publishedCampaign(), 'published');
        $this->actingAs($reviewer)->post("/admin/transparency/{$published->id}/attachments", [
            'file' => UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf'),
        ])->assertSessionHas('error');

        $this->assertSame(0, CampaignUpdateAttachment::count());
        $this->assertSame([], Storage::disk('local')->allFiles('transparency'));
    }

    public function test_evidence_download_is_authorized_and_scoped_to_its_update(): void
    {
        $campaign = $this->publishedCampaign();
        $owner = $this->update($campaign, 'draft');
        $other = $this->update($campaign, 'draft');
        $attachment = $this->attachment($owner);
        $reviewer = $this->user('reviewer');

        $this->actingAs($reviewer)
            ->get("/admin/transparency/{$owner->id}/attachments/{$attachment->id}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDownload('bukti.pdf');

        // IDOR: the attachment id under a different update must not resolve.
        $this->actingAs($reviewer)->get("/admin/transparency/{$other->id}/attachments/{$attachment->id}")->assertNotFound();
        $this->actingAs($reviewer)->delete("/admin/transparency/{$other->id}/attachments/{$attachment->id}")->assertNotFound();

        $this->actingAs($this->user('finance'))->get("/admin/transparency/{$owner->id}/attachments/{$attachment->id}")->assertForbidden();

        $this->assertTrue($attachment->exists && CampaignUpdateAttachment::whereKey($attachment->id)->exists());
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_evidence_can_be_removed_only_while_editable(): void
    {
        $reviewer = $this->user('reviewer');
        $draft = $this->update($this->publishedCampaign(), 'draft');
        $draftFile = $this->attachment($draft);
        $locked = $this->update($this->publishedCampaign(), 'under_review');
        $lockedFile = $this->attachment($locked);

        $this->actingAs($reviewer)->delete("/admin/transparency/{$locked->id}/attachments/{$lockedFile->id}")->assertSessionHas('error');
        Storage::disk('local')->assertExists($lockedFile->path);

        $this->actingAs($reviewer)->delete("/admin/transparency/{$draft->id}/attachments/{$draftFile->id}")->assertSessionHas('success');
        Storage::disk('local')->assertMissing($draftFile->path);
        $this->assertFalse(CampaignUpdateAttachment::whereKey($draftFile->id)->exists());
    }

    public function test_unknown_or_malformed_ids_return_not_found(): void
    {
        $reviewer = $this->user('reviewer');

        $this->actingAs($reviewer)->get('/admin/transparency/00000000-0000-0000-0000-000000000000')->assertNotFound();
        $this->actingAs($reviewer)->get('/admin/transparency/not-a-uuid')->assertNotFound();
    }
}
