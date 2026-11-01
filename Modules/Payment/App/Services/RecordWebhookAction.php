<?php

namespace Modules\Payment\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Payment\Entities\Webhook;

class RecordWebhookAction
{
    public function execute(array $payload, string $provider, string $orderId, ?string $event = null, ?string $signature = null): Webhook
    {
        return DB::transaction(function () use ($payload, $provider, $orderId, $event, $signature) {
            $existing = Webhook::query()
                ->where('provider', $provider)
                ->where('order_id', $orderId)
                ->where('event', $event)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            return Webhook::create([
                'provider' => $provider,
                'event' => $event,
                'order_id' => $orderId,
                'signature' => $signature,
                'payload' => $payload,
            ]);
        });
    }
}
