<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Modules\Payment\App\Services\MayarClient;
use Tests\TestCase;

class MayarClientTest extends TestCase
{
    public function test_mayar_client_uses_v2_sandbox_base_url_and_bearer_key(): void
    {
        Config::set('payment.mayar.api_key', 'sandbox-key');
        Config::set('payment.mayar.base_url', 'https://api.mayar.io/hl/v2');
        Http::fake(['https://api.mayar.io/hl/v2/payments/create' => Http::response([
            'statusCode' => 200,
            'messages' => 'success',
            'data' => ['transactionId' => 'tx-1', 'link' => 'https://testingmayar.myr.id/pl/tx-1'],
        ], 200)]);

        $data = app(MayarClient::class)->createPaymentRequest([
            'name' => 'Donatur Test',
            'amount' => 100000,
            'email' => 'donor@example.test',
            'mobile' => '081234567890',
        ]);

        $this->assertSame('tx-1', $data['transactionId']);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mayar.io/hl/v2/payments/create'
                && $request->hasHeader('Authorization', 'Bearer sandbox-key');
        });
    }

    public function test_mayar_client_fails_without_api_key(): void
    {
        Config::set('payment.mayar.api_key', null);

        $this->expectException(\RuntimeException::class);
        app(MayarClient::class)->getPaymentRequest('payment-id');
    }
}
