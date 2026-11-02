<?php

namespace Modules\Payment\App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Payment\App\Exceptions\MayarRequestException;

/**
 * Mayar Headless API V2 client. The API key is only ever sent as a bearer
 * header; it is never part of an exception message or log context.
 */
class MayarClient
{
    private function request(): PendingRequest
    {
        $apiKey = config('payment.mayar.api_key');

        if (blank($apiKey)) {
            throw new MayarRequestException('MAYAR_API_KEY belum dikonfigurasi.');
        }

        return Http::baseUrl(rtrim((string) config('payment.mayar.base_url'), '/'))
            ->acceptJson()
            ->withToken((string) $apiKey)
            ->timeout((int) config('payment.mayar.timeout', 10))
            ->connectTimeout((int) config('payment.mayar.connect_timeout', 5));
    }

    /** @param array<string, mixed> $payload */
    public function createPaymentRequest(array $payload): array
    {
        return $this->send('create payment', fn (PendingRequest $http) => $http->post('/payments/create', $payload));
    }

    /** Single payment request detail; `$id` is the payment request id (not the transactionId). */
    public function getPaymentRequest(string $id): array
    {
        return $this->send('get payment', fn (PendingRequest $http) => $http->get('/payments/'.rawurlencode($id)));
    }

    /**
     * Server-to-server inquiry: GET /transactions/{transactionId}.
     * `data.status` is one of paid|unpaid|created|expired (Mayar V2 docs).
     */
    public function getTransaction(string $transactionId): array
    {
        return $this->send('inquiry', fn (PendingRequest $http) => $http->get('/transactions/'.rawurlencode($transactionId)));
    }

    /**
     * List provider transactions. The cursor is Mayar's `nextStartingAfter`
     * value; this method intentionally returns only reconciliation-safe fields.
     *
     * @param array<string, scalar> $query
     * @return array{data: array<int, array<string, mixed>>, nextStartingAfter: string|null}
     */
    public function listTransactions(array $query = []): array
    {
        try {
            $response = $this->request()->get('/transactions', $query);
        } catch (ConnectionException) {
            throw new MayarRequestException('Mayar transaction list gagal: timeout atau koneksi terputus.', null, true);
        }

        $this->assertSuccessful($response, 'transaction list');

        $items = $response->json('data', []);

        return [
            'data' => is_array($items) ? array_map(fn ($item): array => is_array($item) ? $this->transactionSummary($item) : [], $items) : [],
            'nextStartingAfter' => $response->json('nextStartingAfter'),
        ];
    }

    /** @param callable(PendingRequest): Response $call */
    private function send(string $operation, callable $call): array
    {
        try {
            $response = $call($this->request());
        } catch (ConnectionException) {
            throw new MayarRequestException("Mayar {$operation} gagal: timeout atau koneksi terputus.", null, true);
        }

        $this->assertSuccessful($response, $operation);

        $data = $response->json('data');

        if (! is_array($data) || $data === []) {
            throw new MayarRequestException("Mayar {$operation} mengembalikan data kosong.", $response->status(), false);
        }

        return $data;
    }

    private function assertSuccessful(Response $response, string $operation): void
    {
        $status = $response->status();
        $bodyStatus = (int) $response->json('statusCode', $status);

        if ($status === 429) {
            $retryAfter = (int) $response->header('Retry-After');
            throw new MayarRequestException("Mayar {$operation} terkena rate limit.", 429, true, $retryAfter > 0 ? $retryAfter : null);
        }

        if ($response->serverError()) {
            throw new MayarRequestException("Mayar {$operation} gagal: server error {$status}.", $status, true);
        }

        if ($response->failed() || $bodyStatus >= 400) {
            $message = Str::limit(strip_tags((string) $response->json('messages', 'Unknown error')), 150);
            throw new MayarRequestException("Mayar {$operation} ditolak ({$bodyStatus}): {$message}", $bodyStatus, false);
        }
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private function transactionSummary(array $item): array
    {
        return [
            'id' => $item['id'] ?? null,
            'createdAt' => $item['createdAt'] ?? null,
            'updatedAt' => $item['updatedAt'] ?? null,
            'status' => $item['status'] ?? null,
            'amount' => $item['amount'] ?? null,
            'paymentMethod' => $item['paymentMethod'] ?? null,
        ];
    }
}
