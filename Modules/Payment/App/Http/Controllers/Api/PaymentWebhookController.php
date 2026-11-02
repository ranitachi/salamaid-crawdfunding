<?php

namespace Modules\Payment\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Payment\App\Jobs\ProcessPaymentWebhookJob;
use Modules\Payment\App\Services\RecordWebhookAction;
use Modules\Payment\App\Services\WebhookSignatureVerifier;
use Throwable;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookSignatureVerifier $verifier,
        private readonly RecordWebhookAction $recorder,
    ) {
    }

    /**
     * Verify → record durably (deduplicated) → hand processing to the queue.
     * A duplicate delivery reuses the same record and is a no-op once processed.
     */
    public function __invoke(Request $request, string $provider): JsonResponse
    {
        $settings = $this->verifier->settings($provider);
        $payload = $request->json()->all();
        $orderId = data_get($payload, $settings['order_id_field'] ?? 'order_id');

        if (! is_string($orderId) || $orderId === '' || ! $this->verifier->verify($request, $provider)) {
            return response()->json(['message' => 'Invalid webhook.'], 401);
        }

        $event = data_get($payload, $settings['event_field'] ?? 'event');
        $signature = $request->header($settings['signature_header'] ?? 'X-Webhook-Signature');
        $webhook = $this->recorder->execute($payload, $provider, $orderId, is_string($event) ? $event : null, $signature);

        if (! $webhook->processed_at) {
            try {
                $pending = ProcessPaymentWebhookJob::dispatch((string) $webhook->id);
                unset($pending); // dispatch now, inside this try (sync queue runs inline)
            } catch (Throwable $exception) {
                Log::warning('payment.webhook.processing_failed', [
                    'webhook_id' => $webhook->id,
                    'provider' => $provider,
                    'error' => mb_substr($exception->getMessage(), 0, 255),
                ]);

                // Stored but not processed: ask the provider to redeliver.
                return response()->json(['received' => true, 'processed' => false, 'id' => $webhook->id], 503);
            }
        }

        return response()->json(['received' => true, 'id' => $webhook->id]);
    }
}
