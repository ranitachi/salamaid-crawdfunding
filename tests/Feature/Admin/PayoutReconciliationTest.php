<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Modules\Operations\App\Services\Admin\PayoutSettlementReconciliationService;
use Modules\Operations\Entities\Payout;
use Modules\Operations\Entities\PayoutRiskFlag;
use Tests\TestCase;

class PayoutReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function organization(string $status = 'verified', int $settled = 1_000_000): Organization
    {
        $organization = Organization::create(['slug' => 'recon-'.Str::random(8), 'name' => 'Recon Org', 'status' => $status, 'verified_at' => $status === 'verified' ? now() : null]);
        $campaign = Campaign::create(['organization_id' => $organization->id, 'title' => 'Recon Campaign', 'description' => 'Test', 'target_amount' => 2_000_000, 'collected_amount' => $settled, 'status' => 'published']);
        Donation::create(['campaign_id' => $campaign->id, 'gross_amount' => $settled, 'net_amount' => $settled, 'method' => 'qris', 'status' => 'paid', 'paid_at' => now()]);
        return $organization;
    }

    public function test_aggregate_detects_overdrawn_reserved_payouts(): void
    {
        $organization = $this->organization(settled: 100_000);
        $requester = User::factory()->create();
        Payout::create(['organization_id' => $organization->id, 'amount' => 150_000, 'status' => 'approved', 'requested_by' => $requester->id]);

        $row = app(PayoutSettlementReconciliationService::class)->forOrganization($organization->id);

        $this->assertSame(100_000, $row->settled_amount);
        $this->assertSame(150_000, $row->reserved_amount);
        $this->assertTrue($row->has_mismatch);
    }

    public function test_reconciliation_page_is_visible_to_finance_but_not_reviewer(): void
    {
        $this->actingAs(User::factory()->role('finance')->create())->get(route('admin.payouts.reconciliation'))->assertOk();
        $this->actingAs(User::factory()->role('reviewer')->create())->get(route('admin.payouts.reconciliation'))->assertForbidden();
    }

    public function test_detail_creates_risk_flag_and_finance_can_review_it(): void
    {
        $organization = $this->organization(status: 'suspended', settled: 100_000);
        $requester = User::factory()->create();
        $payout = Payout::create(['organization_id' => $organization->id, 'amount' => 50_000, 'status' => 'requested', 'requested_by' => $requester->id]);
        $finance = User::factory()->role('finance')->create();

        $this->actingAs($finance)->get(route('admin.payouts.show', $payout))->assertOk()->assertSee('organization_not_verified');
        $flag = PayoutRiskFlag::firstOrFail();
        $this->actingAs($finance)->post(route('admin.payouts.risk-flags.review', $flag), ['status' => 'reviewed'])->assertSessionHas('success');
        $this->assertDatabaseHas('payout_risk_flags', ['id' => $flag->id, 'status' => 'reviewed', 'reviewed_by' => $finance->id]);
    }

    public function test_view_only_role_cannot_review_risk_flag(): void
    {
        $organization = $this->organization(status: 'suspended');
        $payout = Payout::create(['organization_id' => $organization->id, 'amount' => 50_000, 'status' => 'requested', 'requested_by' => User::factory()->create()->id]);
        app(PayoutSettlementReconciliationService::class)->refreshFlags($payout);
        $flag = PayoutRiskFlag::firstOrFail();

        $this->actingAs(User::factory()->role('support')->create())->post(route('admin.payouts.risk-flags.review', $flag), ['status' => 'dismissed'])->assertForbidden();
    }
}
