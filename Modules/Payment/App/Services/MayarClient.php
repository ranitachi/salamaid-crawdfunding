<?php

namespace Modules\Payment\App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MayarClient
{
    private function request(): PendingRequest
    {
        $apiKey = config('payment.mayar.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('MAYAR_API_KEY belum dikonfigurasi.');
        }

        return Http::baseUrl(rtrim((string) config('payment.mayar.base_url'), '/'))
            ->acceptJson()
            ->withToken((string) $apiKey)
            ->timeout(15)
            ->connectTimeout(5);
    }

    /** @param array<string, mixed> $payload */
    public function createPaymentRequest(array $payload): array
    {
        $response = $this->request()->post('/payments/create', $payload);

        if ($response->failed() || (int) $response->json('statusCode', $response->status()) >= 400) {
            throw new RuntimeException('Mayar create payment gagal: '.(string) $response->json('messages', 'Unknown error'));
        }

        return (array) $response->json('data', []);
    }

    public function getPaymentRequest(string $id): array
    {
        $response = $this->request()->get('/payments/'.urlencode($id));

        if ($response->failed() || (int) $response->json('statusCode', $response->status()) >= 400) {
            throw new RuntimeException('Mayar get payment gagal: '.(string) $response->json('messages', 'Unknown error'));
        }

        return (array) $response->json('data', []);
    }
}
