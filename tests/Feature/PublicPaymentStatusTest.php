<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Modules\Payment\Entities\Payment;
use Tests\TestCase;

class PublicPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(): Campaign
    {
        $organization = Organization::create(['slug' => 'status-'.Str::random(8), 'name' => 'Status Organization', 'status' => 'verified', 'verified_at' => now()]);
        return Campaign::create(['organization_id' => $organization->id, 'title' => 'Status Campaign', 'slug' => 'status-campaign-'.Str::random(5), 'description' => 'Cerita', 'target_amount' => 1_000_000, 'status' => 'published']);
    }

    private function payment(Campaign $campaign, ?int $userId = null, string $status = 'pending'): Payment
    {
        $donation = Donation::create(['campaign_id' => $campaign->id, 'user_id' => $userId, 'gross_amount' => 50000, 'net_amount' => 50000, 'method' => 'mayar', 'status' => $status, 'paid_at' => $status === 'paid' ? now() : null]);
        return Payment::create(['donation_id' => $donation->id, 'provider' => 'mayar', 'order_id' => 'status-'.Str::uuid(), 'channel_code' => 'mayar_checkout', 'redirect_url' => 'https://web.mayar.io/payment/status', 'status' => $status, 'paid_at' => $status === 'paid' ? now() : null]);
    }

    public function test_owner_can_view_status_but_another_user_cannot(): void
    {
        $campaign = $this->campaign();
        $owner = User::factory()->create();
        $payment = $this->payment($campaign, $owner->id);

        $this->actingAs($owner)->get(route('public.payments.status', $payment->donation_id))->assertOk()->assertSee('Menunggu pembayaran');
        $this->actingAs(User::factory()->create())->get(route('public.payments.status', $payment->donation_id))->assertForbidden();
    }

    public function test_guest_can_view_only_a_signed_status_url(): void
    {
        $campaign = $this->campaign();
        $payment = $this->payment($campaign);
        $url = URL::temporarySignedRoute('public.payments.status', now()->addMinutes(10), ['donation' => $payment->donation_id]);

        $this->get($url)->assertOk()->assertSee('Menunggu pembayaran');
        $this->get(route('public.payments.status', $payment->donation_id))->assertForbidden();
    }

    public function test_signed_refresh_applies_provider_paid_status(): void
    {
        $campaign = $this->campaign();
        $payment = $this->payment($campaign);
        Http::fake(['*/transactions/*' => Http::response(['data' => ['id' => $payment->order_id, 'status' => 'paid', 'amount' => 50000]], 200)]);
        $url = URL::temporarySignedRoute('public.payments.status.refresh', now()->addMinutes(10), ['donation' => $payment->donation_id]);

        $this->post($url)->assertRedirect()->assertSessionHas('status_message');
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('donations', ['id' => $payment->donation_id, 'status' => 'paid']);
    }
}
