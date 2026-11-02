<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Modules\Payment\Entities\Payment;
use Tests\TestCase;

class MayarTransactionExplorerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Config::set('payment.mayar.api_key', 'test-key');
        Config::set('payment.mayar.base_url', 'https://api.mayar.io/hl/v2');
    }

    public function test_payment_view_role_can_browse_mayar_transactions_with_cursor(): void
    {
        Http::fake([
            'https://api.mayar.io/hl/v2/transactions*' => Http::response([
                'statusCode' => 200,
                'data' => [[
                    'id' => 'mayar-tx-1', 'status' => 'paid', 'amount' => 250000,
                    'paymentMethod' => 'QRIS', 'createdAt' => 1760000000000,
                    'updatedAt' => 1760000001000, 'customer' => ['email' => 'private@example.test'],
                ]],
                'nextStartingAfter' => '1760000000000',
            ]),
        ]);

        $response = $this->actingAs(User::factory()->role('support')->create())
            ->get('/admin/mayar-transactions?status=paid');

        $response->assertOk()
            ->assertSee('mayar-tx-1')
            ->assertSee('QRIS')
            ->assertDontSee('private@example.test')
            ->assertSee('Berikutnya');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.mayar.io/hl/v2/transactions?status=paid&limit=20&fields=id%2CcreatedAt%2CupdatedAt%2Cstatus%2Camount%2CpaymentMethod');
    }

    public function test_payment_view_role_can_open_mayar_transaction_detail(): void
    {
        Http::fake([
            'https://api.mayar.io/hl/v2/transactions/mayar-tx-1' => Http::response([
                'statusCode' => 200,
                'data' => [
                    'id' => 'mayar-tx-1', 'status' => 'paid', 'amount' => 250000,
                    'paymentMethod' => 'QRIS', 'invoiceCode' => 'INV-1',
                    'createdAt' => 1760000000000, 'updatedAt' => 1760000001000,
                    'customer' => ['email' => 'private@example.test'],
                ],
            ]),
        ]);

        $this->actingAs(User::factory()->role('support')->create())
            ->get('/admin/mayar-transactions/mayar-tx-1')
            ->assertOk()
            ->assertSee('mayar-tx-1')
            ->assertSee('INV-1')
            ->assertDontSee('private@example.test');
    }

    public function test_local_payment_id_is_resolved_to_provider_transaction_id(): void
    {
        $organization = Organization::create(['slug' => 'org-local-payment', 'name' => 'Org']);
        $campaign = Campaign::create([
            'organization_id' => $organization->id, 'title' => 'Campaign',
            'description' => 'Deskripsi', 'target_amount' => 1_000_000, 'status' => 'published',
        ]);
        $donation = Donation::create([
            'campaign_id' => $campaign->id, 'gross_amount' => 250000,
            'net_amount' => 250000, 'method' => 'mayar', 'status' => 'pending',
        ]);
        $payment = Payment::create([
            'donation_id' => $donation->id, 'provider' => 'mayar',
            'order_id' => 'mayar-provider-tx-1', 'channel_code' => 'mayar_checkout', 'status' => 'pending',
        ]);
        Http::fake([
            'https://api.mayar.io/hl/v2/transactions/mayar-provider-tx-1' => Http::response([
                'statusCode' => 200,
                'data' => ['id' => 'mayar-provider-tx-1', 'status' => 'paid', 'amount' => 250000, 'paymentMethod' => 'QRIS'],
            ]),
        ]);

        $this->actingAs(User::factory()->role('support')->create())
            ->get('/admin/mayar-transactions/'.$payment->id)
            ->assertOk()
            ->assertSee('mayar-provider-tx-1')
            ->assertSee((string) $payment->id);
    }

    public function test_payment_request_id_falls_back_when_transaction_lookup_returns_not_found(): void
    {
        Http::fake([
            'https://api.mayar.io/hl/v2/transactions/payment-request-1' => Http::response([
                'statusCode' => 404, 'messages' => 'Transaction not found',
            ], 404),
            'https://api.mayar.io/hl/v2/transactions?*' => Http::response([
                'statusCode' => 200, 'data' => [], 'nextStartingAfter' => null,
            ]),
            'https://api.mayar.io/hl/v2/payments/payment-request-1' => Http::response([
                'statusCode' => 200,
                'data' => ['id' => 'payment-request-1', 'status' => 'paid', 'amount' => 250000, 'link' => 'pay-link'],
            ]),
        ]);

        $this->actingAs(User::factory()->role('support')->create())
            ->get('/admin/mayar-transactions/payment-request-1')
            ->assertOk()
            ->assertSee('payment-request-1')
            ->assertSee('Rp 250.000');
    }

    public function test_settled_transaction_falls_back_to_provider_list_when_detail_returns_not_found(): void
    {
        Http::fake([
            'https://api.mayar.io/hl/v2/transactions/settled-1' => Http::response([
                'statusCode' => 404, 'messages' => 'Transaction not found',
            ], 404),
            'https://api.mayar.io/hl/v2/transactions?*' => Http::response([
                'statusCode' => 200,
                'data' => [[
                    'id' => 'settled-1', 'status' => 'settled', 'amount' => 10000,
                    'paymentMethod' => 'Transfer VA - BNI', 'createdAt' => 1775991660429,
                ]],
                'nextStartingAfter' => null,
            ]),
        ]);

        $this->actingAs(User::factory()->role('support')->create())
            ->get('/admin/mayar-transactions/settled-1')
            ->assertOk()
            ->assertSee('settled-1')
            ->assertSee('Transfer VA - BNI')
            ->assertSee('Rp 10.000');
    }

    public function test_reviewer_without_payment_permission_cannot_browse_mayar_transactions(): void
    {
        $this->actingAs(User::factory()->role('reviewer')->create())
            ->get('/admin/mayar-transactions')
            ->assertForbidden();
    }
}
