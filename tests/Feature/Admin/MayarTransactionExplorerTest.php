<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
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

    public function test_reviewer_without_payment_permission_cannot_browse_mayar_transactions(): void
    {
        $this->actingAs(User::factory()->role('reviewer')->create())
            ->get('/admin/mayar-transactions')
            ->assertForbidden();
    }
}
