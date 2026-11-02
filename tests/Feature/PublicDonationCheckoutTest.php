<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Modules\Payment\Entities\Payment;
use Tests\TestCase;

class PublicDonationCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(string $status = 'published'): Campaign
    {
        $organization = Organization::create(['slug' => 'public-'.Str::random(8), 'name' => 'Public Organization', 'status' => 'verified', 'verified_at' => now()]);
        return Campaign::create(['organization_id' => $organization->id, 'title' => 'Public Campaign', 'slug' => 'public-campaign-'.Str::random(5), 'description' => 'Cerita publik', 'target_amount' => 1_000_000, 'status' => $status, 'is_featured' => true]);
    }

    public function test_published_verified_campaign_shows_checkout(): void
    {
        $campaign = $this->campaign();
        $this->get(route('public.campaigns.donate', $campaign->slug))->assertOk()->assertSee('Alirkan kebaikan');
        $this->get(route('public.campaigns.donate', $this->campaign('draft')->slug))->assertNotFound();
    }

    public function test_checkout_creates_pending_donation_and_mayar_payment(): void
    {
        $campaign = $this->campaign();
        Http::fake(['*/payments/create' => Http::response(['data' => ['transactionId' => 'mayar-checkout-test', 'link' => 'https://web.mayar.io/payment/mayar-checkout-test']], 200)]);

        $this->post(route('public.campaigns.donate.store', $campaign->slug), [
            'name' => 'Demo Donor', 'email' => 'donor@example.test', 'mobile' => '+628120000000',
            'amount' => 50000, 'anonymous' => 1, 'message' => 'Semoga bermanfaat.',
        ])->assertRedirect('https://web.mayar.io/payment/mayar-checkout-test');

        $this->assertDatabaseHas('donations', ['campaign_id' => $campaign->id, 'gross_amount' => 50000, 'status' => 'pending', 'anonymous' => 1]);
        $this->assertDatabaseHas('payments', ['order_id' => 'mayar-checkout-test', 'provider' => 'mayar', 'status' => 'pending']);
    }

    public function test_checkout_validates_amount_and_does_not_call_provider(): void
    {
        $campaign = $this->campaign();
        Http::fake();

        $this->from(route('public.campaigns.donate', $campaign->slug))->post(route('public.campaigns.donate.store', $campaign->slug), [
            'name' => 'Demo Donor', 'email' => 'donor@example.test', 'mobile' => '+628120000000', 'amount' => 500,
        ])->assertRedirect(route('public.campaigns.donate', $campaign->slug))->assertSessionHasErrors('amount');

        $this->assertSame(0, Donation::count());
        Http::assertNothingSent();
    }
}
