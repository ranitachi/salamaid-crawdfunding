<?php

namespace Tests\Feature\Admin;

use App\Contracts\AdminAuditLogger;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Modules\Operations\Entities\Payout;
use Tests\TestCase;

class PayoutEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n";

    /** @var array<int, array<string, mixed>> */
    private array $audits = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('private');
        Storage::fake('public');

        $audits = &$this->audits;
        $this->app->instance(AdminAuditLogger::class, new class($audits) implements AdminAuditLogger {
            public function __construct(private array &$audits)
            {
            }

            public function log(string $action, Model $subject, array $before, array $after, ?string $reason = null, ?User $actor = null, array $context = []): void
            {
                $this->audits[] = compact('action', 'before', 'after');
            }
        });
    }

    private function approvedPayout(int $settled = 2_000_000, int $amount = 1_000_000): Payout
    {
        $organization = Organization::create([
            'slug' => 'org-'.Str::random(8), 'name' => 'Org Payout', 'status' => 'verified', 'verified_at' => now(),
        ]);
        $campaign = Campaign::create([
            'organization_id' => $organization->id, 'title' => 'Campaign', 'description' => 'Deskripsi',
            'target_amount' => 10_000_000, 'collected_amount' => $settled, 'status' => 'published',
        ]);
        Donation::create([
            'campaign_id' => $campaign->id, 'gross_amount' => $settled, 'net_amount' => $settled,
            'method' => 'qris', 'status' => 'paid', 'paid_at' => now(),
        ]);

        $payout = Payout::create([
            'organization_id' => $organization->id, 'amount' => $amount, 'status' => 'approved',
            'requested_by' => User::factory()->role('admin')->create()->id,
        ]);

        return $payout;
    }

    private function finance(): User
    {
        return User::factory()->role('finance')->create();
    }

    private function pdf(string $name = 'bukti.pdf'): UploadedFile
    {
        return $this->upload($name, self::PDF);
    }

    /**
     * A real UploadedFile backed by a temp file, so MIME detection reads the
     * content (finfo). UploadedFile::fake() reports a MIME guessed from the name.
     */
    private function upload(string $name, string $content, string $clientMime = 'application/pdf'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'payout-evidence-');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, $clientMime, null, true);
    }

    private function markPaid(Payout $payout, array $data, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->finance())->post(route('admin.payouts.paid', $payout), $data);
    }

    // ---- Private upload --------------------------------------------------

    public function test_uploaded_evidence_is_stored_on_private_disk_with_server_metadata(): void
    {
        $payout = $this->approvedPayout();

        $this->markPaid($payout, ['evidence_file' => $this->pdf('../../transfer <x>.pdf')])
            ->assertRedirect(route('admin.payouts.show', $payout))
            ->assertSessionHas('success');

        $payout->refresh();
        $this->assertSame('paid', $payout->status);
        $this->assertStringStartsWith("payouts/evidence/{$payout->id}/", $payout->evidence_path);
        $this->assertStringNotContainsString('transfer', $payout->evidence_path, 'Stored name is generated, not client supplied.');
        $this->assertSame('application/pdf', $payout->evidence_mime);
        $this->assertSame(strlen(self::PDF), $payout->evidence_size);
        $this->assertNull($payout->evidence_url, 'No public URL is generated for private evidence.');
        $this->assertTrue($payout->hasPrivateEvidence());

        Storage::disk('private')->assertExists($payout->evidence_path);
        Storage::disk('public')->assertMissing($payout->evidence_path);
        $this->assertFileDoesNotExist(public_path($payout->evidence_path));
        $this->assertFileDoesNotExist(public_path('storage/'.$payout->evidence_path));
    }

    public function test_png_upload_is_accepted(): void
    {
        $payout = $this->approvedPayout();

        $this->markPaid($payout, ['evidence_file' => UploadedFile::fake()->image('bukti.png', 20, 20)])->assertSessionHas('success');

        $this->assertSame('image/png', $payout->fresh()->evidence_mime);
    }

    public function test_upload_type_is_checked_from_content_not_client_mime(): void
    {
        $payout = $this->approvedPayout();

        // PHP payload disguised as a PDF (name and client MIME say PDF).
        $disguised = $this->upload('bukti.pdf', "<?php system(\$_GET['c']); ?>");
        $this->markPaid($payout, ['evidence_file' => $disguised])->assertSessionHasErrors('evidence_file');

        $this->markPaid($payout, ['evidence_file' => $this->upload('bukti.png', "GIF89a\x01\x00\x01\x00\x00\x00\x00;", 'image/png')])
            ->assertSessionHasErrors('evidence_file');

        $this->markPaid($payout, ['evidence_file' => UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf')])
            ->assertSessionHasErrors('evidence_file');

        $this->assertSame('approved', $payout->fresh()->status);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    // ---- Validation of the evidence source -------------------------------

    public function test_exactly_one_evidence_source_is_required(): void
    {
        $payout = $this->approvedPayout();

        $this->markPaid($payout, [])->assertSessionHasErrors('evidence');
        $this->markPaid($payout, ['evidence_file' => $this->pdf(), 'evidence_url' => 'https://bank.example/bukti/1'])
            ->assertSessionHasErrors('evidence');

        $this->assertSame('approved', $payout->fresh()->status);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_legacy_url_formats_remain_supported(): void
    {
        $viaUrlField = $this->approvedPayout();
        $this->markPaid($viaUrlField, ['evidence_url' => 'https://bank.example/bukti/1'])->assertSessionHas('success');
        $this->assertSame('https://bank.example/bukti/1', $viaUrlField->fresh()->evidence_url);
        $this->assertNull($viaUrlField->fresh()->evidence_path);

        $viaLegacyField = $this->approvedPayout();
        $this->markPaid($viaLegacyField, ['evidence' => 'http://bank.example/bukti/2'])->assertSessionHas('success');
        $this->assertSame('http://bank.example/bukti/2', $viaLegacyField->fresh()->evidence_url);
        $this->assertFalse($viaLegacyField->fresh()->hasPrivateEvidence(), 'External URL is never treated as a private file.');
    }

    public function test_unsafe_urls_are_rejected(): void
    {
        $payout = $this->approvedPayout();

        foreach (['javascript:alert(1)', 'ftp://bank.example/bukti', 'file:///etc/passwd', 'not a url'] as $value) {
            $this->markPaid($payout, ['evidence_url' => $value])->assertSessionHasErrors('evidence_url');
            $this->markPaid($payout, ['evidence' => $value])->assertSessionHasErrors('evidence');
        }

        $this->assertSame('approved', $payout->fresh()->status);
    }

    public function test_private_path_string_is_limited_to_the_payouts_own_existing_file(): void
    {
        $payout = $this->approvedPayout();
        $other = $this->approvedPayout();
        $name = Str::random(40);
        Storage::disk('private')->put("payouts/evidence/{$payout->id}/{$name}.pdf", self::PDF);
        Storage::disk('private')->put("payouts/evidence/{$other->id}/{$name}.pdf", self::PDF);

        foreach ([
            '../../../.env',
            "payouts/evidence/{$payout->id}/../{$other->id}/{$name}.pdf",
            "payouts/evidence/{$other->id}/{$name}.pdf",      // another payout's file (IDOR)
            "payouts/evidence/{$payout->id}/{$name}.php",
            "/payouts/evidence/{$payout->id}/{$name}.pdf",
        ] as $path) {
            $this->markPaid($payout, ['evidence' => $path])->assertSessionHasErrors('evidence');
        }

        // Defence in depth: a stored file whose content is not an allowed type is rejected by the service.
        $disguised = Str::random(40);
        Storage::disk('private')->put("payouts/evidence/{$payout->id}/{$disguised}.pdf", "<?php echo 'x'; ?>");
        $this->markPaid($payout, ['evidence' => "payouts/evidence/{$payout->id}/{$disguised}.pdf"])->assertSessionHas('error');

        // Well-formed but missing file → rejected by the service via Storage.
        $this->markPaid($payout, ['evidence' => "payouts/evidence/{$payout->id}/".Str::random(40).'.pdf'])
            ->assertSessionHas('error');
        $this->assertSame('approved', $payout->fresh()->status);

        $this->markPaid($payout, ['evidence' => "payouts/evidence/{$payout->id}/{$name}.pdf"])->assertSessionHas('success');
        $this->assertSame('application/pdf', $payout->fresh()->evidence_mime);
        $this->assertSame(strlen(self::PDF), $payout->fresh()->evidence_size);
    }

    // ---- Business rules ----------------------------------------------------

    public function test_balance_is_rechecked_before_paid_and_upload_is_cleaned_up(): void
    {
        $payout = $this->approvedPayout(settled: 2_000_000, amount: 1_000_000);
        Donation::query()->update(['status' => 'refund']); // settled funds dropped after approval

        $this->markPaid($payout, ['evidence_file' => $this->pdf()])->assertSessionHas('error');

        $this->assertSame('approved', $payout->fresh()->status);
        $this->assertSame([], Storage::disk('private')->allFiles(), 'No orphan evidence file.');
    }

    public function test_only_approved_payouts_can_be_marked_paid(): void
    {
        $payout = $this->approvedPayout();
        $payout->forceFill(['status' => 'requested'])->save();

        $this->markPaid($payout, ['evidence_file' => $this->pdf()])->assertSessionHas('error');

        $this->assertSame('requested', $payout->fresh()->status);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_view_only_and_unrelated_roles_cannot_mark_paid(): void
    {
        $payout = $this->approvedPayout();

        foreach (['support', 'admin'] as $role) {
            $this->markPaid($payout, ['evidence_url' => 'https://bank.example/x'], User::factory()->role($role)->create())
                ->assertForbidden();
        }
        $this->markPaid($payout, ['evidence_url' => 'https://bank.example/x'], User::factory()->role('reviewer')->create())
            ->assertForbidden();

        $this->assertSame('approved', $payout->fresh()->status);
    }

    public function test_audit_has_evidence_metadata_without_tokens_or_contents(): void
    {
        $payout = $this->approvedPayout();
        $this->markPaid($payout, ['evidence_url' => 'https://bank.example/bukti/9?token=SECRET-SIGNATURE#frag'])->assertSessionHas('success');

        $paid = collect($this->audits)->firstWhere('action', 'payout.paid');
        $this->assertSame('https://bank.example/bukti/9', $paid['after']['evidence_url']);
        $this->assertSame('url', $paid['after']['evidence_type']);
        $this->assertSame('approved', $paid['before']['status']);
        $this->assertStringNotContainsString('SECRET-SIGNATURE', json_encode($this->audits));
        $this->assertSame('paid', $payout->fresh()->status);
        $this->assertStringContainsString('SECRET-SIGNATURE', $payout->fresh()->evidence_url, 'Stored link itself is unchanged.');

        $file = $this->approvedPayout();
        $this->markPaid($file, ['evidence_file' => $this->pdf()])->assertSessionHas('success');
        $audit = json_encode(collect($this->audits)->last());
        $this->assertStringNotContainsString('%PDF', $audit, 'File contents never reach the audit log.');
        $this->assertStringContainsString('private_file', $audit);
    }

    // ---- Download ----------------------------------------------------------

    public function test_finance_downloads_evidence_with_server_side_mime(): void
    {
        $payout = $this->approvedPayout();
        $this->markPaid($payout, ['evidence_file' => $this->pdf()])->assertSessionHas('success');

        $response = $this->actingAs($this->finance())->get(route('admin.payouts.evidence', $payout));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDownload("bukti-payout-{$payout->id}.pdf");
        $this->assertSame(self::PDF, $response->streamedContent());

        $this->actingAs(User::factory()->role('super_admin')->create())
            ->get(route('admin.payouts.evidence', $payout))->assertOk();
    }

    public function test_download_requires_payouts_manage(): void
    {
        $payout = $this->approvedPayout();
        $this->markPaid($payout, ['evidence_file' => $this->pdf()])->assertSessionHas('success');

        $this->app['auth']->guard('web')->logout(); // markPaid() above acted as finance
        $this->get(route('admin.payouts.evidence', $payout))->assertRedirect(route('admin.login'));

        foreach (['support', 'admin'] as $role) { // payouts.view only
            $this->actingAs(User::factory()->role($role)->create())
                ->get(route('admin.payouts.evidence', $payout))->assertForbidden();
        }
        foreach (['reviewer', 'user'] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get(route('admin.payouts.evidence', $payout))->assertForbidden();
        }
    }

    public function test_download_is_scoped_to_the_bound_payout(): void
    {
        $finance = $this->finance();
        $withFile = $this->approvedPayout();
        $this->markPaid($withFile, ['evidence_file' => $this->pdf()])->assertSessionHas('success');

        // Legacy URL payout: no private file to stream.
        $legacy = $this->approvedPayout();
        $this->markPaid($legacy, ['evidence_url' => 'https://bank.example/bukti/3'])->assertSessionHas('success');
        $this->actingAs($finance)->get(route('admin.payouts.evidence', $legacy))->assertForbidden();

        // A path pointing at another payout's file (tampered row) is never served.
        $tampered = $this->approvedPayout();
        $tampered->forceFill(['evidence_path' => $withFile->fresh()->evidence_path])->save();
        $this->actingAs($finance)->get(route('admin.payouts.evidence', $tampered))->assertForbidden();

        // File missing on disk → 404.
        Storage::disk('private')->delete($withFile->fresh()->evidence_path);
        $this->actingAs($finance)->get(route('admin.payouts.evidence', $withFile))->assertNotFound();

        $this->actingAs($finance)->get('/admin/payouts/not-a-uuid/evidence')->assertNotFound();
        $this->actingAs($finance)->get('/admin/payouts/'.Str::uuid().'/evidence')->assertNotFound();
    }

    public function test_detail_page_shows_private_and_legacy_evidence(): void
    {
        $private = $this->approvedPayout();
        $this->markPaid($private, ['evidence_file' => $this->pdf()])->assertSessionHas('success');
        $legacy = $this->approvedPayout();
        $legacy->forceFill(['status' => 'paid', 'paid_at' => now(), 'evidence_url' => 'https://bank.example/bukti/lama'])->save();

        $this->actingAs($this->finance())->get(route('admin.payouts.show', $private))
            ->assertOk()->assertSee(route('admin.payouts.evidence', $private))->assertSee('application/pdf');

        $this->actingAs(User::factory()->role('support')->create())->get(route('admin.payouts.show', $private))
            ->assertOk()->assertSee('File privat')->assertDontSee(route('admin.payouts.evidence', $private));

        $this->actingAs($this->finance())->get(route('admin.payouts.show', $legacy))
            ->assertOk()->assertSee('https://bank.example/bukti/lama')->assertSee('URL lama');

        $approved = $this->approvedPayout();
        $this->actingAs($this->finance())->get(route('admin.payouts.show', $approved))
            ->assertOk()->assertSee('enctype="multipart/form-data"', false)->assertSee('name="evidence_file"', false);
    }
}
