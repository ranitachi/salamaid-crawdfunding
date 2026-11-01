<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Modules\Operations\Entities\Payout;
use Tests\TestCase;

class PayoutOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function organizationWithSettledDonation(int $amount = 2_000_000): Organization
    {
        $organization = Organization::create([
            'slug' => 'payout-org-'.Str::random(8),
            'name' => 'Payout Organization',
            'status' => 'verified',
            'verified_at' => now(),
        ]);
        $campaign = Campaign::create([
            'organization_id' => $organization->id,
            'title' => 'Payout Campaign',
            'description' => 'Campaign untuk pengujian payout.',
            'target_amount' => 10_000_000,
            'collected_amount' => $amount,
            'status' => 'published',
        ]);
        $donor = User::factory()->create();
        Donation::create([
            'campaign_id' => $campaign->id,
            'user_id' => $donor->id,
            'gross_amount' => $amount,
            'net_amount' => $amount,
            'method' => 'qris',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $organization;
    }

    private function payout(Organization $organization, User $requester, int $amount = 1_000_000): Payout
    {
        return Payout::create([
            'organization_id' => $organization->id,
            'amount' => $amount,
            'status' => 'requested',
            'requested_by' => $requester->id,
            'note' => 'Pengajuan payout test',
        ]);
    }

    public function test_finance_can_approve_payout_with_sufficient_balance(): void
    {
        $finance = User::factory()->role('finance')->create();
        $requester = User::factory()->role('admin')->create();
        $payout = $this->payout($this->organizationWithSettledDonation(), $requester);

        $this->actingAs($finance)
            ->post(route('admin.payouts.approve', $payout))
            ->assertRedirect(route('admin.payouts.show', $payout))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payouts', [
            'id' => $payout->id,
            'status' => 'approved',
            'approved_by' => $finance->id,
        ]);
    }

    public function test_requester_cannot_approve_own_payout(): void
    {
        $requester = User::factory()->role('finance')->create();
        $payout = $this->payout($this->organizationWithSettledDonation(), $requester);

        $this->actingAs($requester)
            ->post(route('admin.payouts.approve', $payout))
            ->assertForbidden();
    }

    public function test_payout_over_available_balance_is_rejected(): void
    {
        $finance = User::factory()->role('finance')->create();
        $requester = User::factory()->role('admin')->create();
        $payout = $this->payout($this->organizationWithSettledDonation(500_000), $requester, 1_000_000);

        $this->actingAs($finance)
            ->post(route('admin.payouts.approve', $payout))
            ->assertRedirect(route('admin.payouts.show', $payout))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payouts', ['id' => $payout->id, 'status' => 'requested']);
    }

    public function test_reject_requires_reason_and_mark_paid_requires_evidence(): void
    {
        $finance = User::factory()->role('finance')->create();
        $requester = User::factory()->role('admin')->create();
        $payout = $this->payout($this->organizationWithSettledDonation(), $requester);

        $this->actingAs($finance)
            ->post(route('admin.payouts.reject', $payout), [])
            ->assertSessionHasErrors('reason');

        $this->actingAs($finance)
            ->post(route('admin.payouts.approve', $payout))
            ->assertSessionHas('success');

        $this->actingAs($finance)
            ->post(route('admin.payouts.paid', $payout), [])
            ->assertSessionHasErrors('evidence_url');

        $this->actingAs($finance)
            ->post(route('admin.payouts.paid', $payout), ['evidence_url' => 'https://example.test/evidence/1'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payouts', ['id' => $payout->id, 'status' => 'paid']);
    }
}
