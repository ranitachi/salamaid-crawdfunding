<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Category;
use Modules\Core\Entities\Organization;
use Modules\Donation\App\Queries\Admin\DonationMonitoringQuery;
use Modules\Donation\Entities\Donation;
use Modules\Payment\App\Queries\Admin\PaymentMonitoringQuery;
use Modules\Payment\App\Services\Admin\PaymentReconciliationService;
use Modules\Payment\App\Services\RecordWebhookAction;
use Modules\Payment\Entities\Payment;
use Tests\TestCase;

class DonationPaymentMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function createDonation(string $donationStatus = 'paid', string $paymentStatus = 'paid'): Donation
    {
        $organization = Organization::create([
            'slug' => 'org-'.fake()->unique()->slug(),
            'name' => 'Demo Organization',
        ]);
        $category = Category::create(['name' => 'Pendidikan', 'slug' => 'cat-'.fake()->unique()->slug()]);
        $campaign = Campaign::create([
            'organization_id' => $organization->id,
            'category_id' => $category->id,
            'title' => 'Demo Campaign',
            'description' => 'Campaign untuk test monitoring.',
            'target_amount' => 1000000,
            'status' => 'published',
        ]);
        $user = User::factory()->create();
        $donation = Donation::create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'gross_amount' => 100000,
            'net_amount' => 100000,
            'method' => 'qris',
            'status' => $donationStatus,
            'paid_at' => $donationStatus === 'paid' ? now() : null,
        ]);

        Payment::create([
            'donation_id' => $donation->id,
            'provider' => 'test',
            'order_id' => 'ORDER-'.fake()->unique()->numerify('#####'),
            'channel_code' => 'QRIS',
            'status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'paid' ? now() : null,
        ]);

        return $donation;
    }

    public function test_donation_and_payment_monitoring_queries_load_related_records(): void
    {
        $donation = $this->createDonation();

        $donations = app(DonationMonitoringQuery::class)->paginate(Request::create('/admin/donations', 'GET', [
            'status' => 'paid',
        ]));
        $payments = app(PaymentMonitoringQuery::class)->paginate(Request::create('/admin/payments', 'GET', [
            'status' => 'paid',
        ]));

        $this->assertSame($donation->id, $donations->first()->id);
        $this->assertTrue($donations->first()->relationLoaded('payment'));
        $this->assertTrue($payments->first()->relationLoaded('donation'));
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        $action = app(RecordWebhookAction::class);
        $payload = ['order_id' => 'ORDER-IDEMPOTENT', 'status' => 'paid'];

        $first = $action->execute($payload, 'test', 'ORDER-IDEMPOTENT', 'payment.paid', 'signature');
        $second = $action->execute($payload, 'test', 'ORDER-IDEMPOTENT', 'payment.paid', 'signature');

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('webhooks', 1);
    }

    public function test_reconciliation_detects_status_mismatch(): void
    {
        $this->createDonation('pending', 'paid');

        $mismatches = app(PaymentReconciliationService::class)->mismatches();

        $this->assertCount(1, $mismatches['paid_payment_not_paid_donation']);
        $this->assertCount(0, $mismatches['paid_donation_without_paid_payment']);
    }

    public function test_valid_provider_webhook_is_verified_and_updates_payment(): void
    {
        $donation = $this->createDonation('pending', 'pending');
        $payment = $donation->payment;
        Config::set('payment.webhooks.providers.test', [
            'algorithm' => 'sha256',
            'secret' => 'test-secret',
            'signature_header' => 'X-Test-Signature',
            'order_id_field' => 'order_id',
            'event_field' => 'event',
        ]);

        $payload = json_encode(['order_id' => $payment->order_id, 'event' => 'payment.paid', 'status' => 'paid']);
        $response = $this->withHeaders([
            'Content-Type' => 'application/json',
            'X-Test-Signature' => hash_hmac('sha256', $payload, 'test-secret'),
        ])->postJson('/api/v1/payment/webhooks/test', json_decode($payload, true));

        $response->assertOk()->assertJson(['received' => true]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('donations', ['id' => $donation->id, 'status' => 'paid']);
        $this->assertDatabaseHas('webhooks', ['order_id' => $payment->order_id]);
    }

    public function test_invalid_provider_webhook_is_rejected_without_persisting(): void
    {
        Config::set('payment.webhooks.providers.test', [
            'algorithm' => 'sha256', 'secret' => 'test-secret',
            'signature_header' => 'X-Test-Signature', 'order_id_field' => 'order_id',
        ]);

        $response = $this->withHeaders(['X-Test-Signature' => 'invalid'])
            ->postJson('/api/v1/payment/webhooks/test', ['order_id' => 'UNTRUSTED', 'status' => 'paid']);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('webhooks', 0);
    }

    public function test_mayar_webhook_uses_callback_token_and_nested_transaction_payload(): void
    {
        $donation = $this->createDonation('pending', 'pending');
        $payment = $donation->payment;
        $payment->update(['order_id' => 'MAYAR-TRANSACTION-1']);
        Config::set('payment.mayar.webhook_token', 'mayar-test-token');
        Config::set('payment.webhooks.providers.mayar.token', 'mayar-test-token');

        $response = $this->postJson('/api/v1/payment/webhooks/mayar?token=mayar-test-token', [
            'event' => 'payment.received',
            'data' => [
                'transactionId' => 'MAYAR-TRANSACTION-1',
                'status' => 'SUCCESS',
            ],
        ]);

        $response->assertOk()->assertJson(['received' => true]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('donations', ['id' => $donation->id, 'status' => 'paid']);
    }
}
