<?php

namespace Modules\Payment\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\App\Services\ProcessWebhookAction;
use Modules\Payment\App\Services\WebhookSignatureVerifier;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookSignatureVerifier $verifier,
        private readonly ProcessWebhookAction $processor,
    ) {
    }

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
        $webhook = $this->processor->execute($payload, $provider, $orderId, is_string($event) ? $event : null, $signature);

        return response()->json(['received' => true, 'id' => $webhook->id]);
    }
}
