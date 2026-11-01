<?php

namespace Modules\Payment\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Donation\Entities\Donation;
use Modules\Payment\Entities\Payment;
use Modules\Payment\Entities\Webhook;

class ProcessWebhookAction
{
    public function __construct(private readonly RecordWebhookAction $recorder)
    {
    }

    public function execute(array $payload, string $provider, string $orderId, ?string $event, ?string $signature): Webhook
    {
        return DB::transaction(function () use ($payload, $provider, $orderId, $event, $signature) {
            $webhook = $this->recorder->execute($payload, $provider, $orderId, $event, $signature);
            $payment = Payment::query()->where('order_id', $orderId)->lockForUpdate()->first();

            if (! $payment || $webhook->processed_at) {
                return $webhook;
            }

            $settings = (array) config("payment.webhooks.providers.{$provider}", []);
            $status = strtolower((string) data_get($payload, $settings['status_field'] ?? 'status', ''));
            $paidStatuses = array_map('strtolower', config('payment.webhooks.paid_statuses', []));
            $failedStatuses = array_map('strtolower', config('payment.webhooks.failed_statuses', []));

            if (in_array($status, $paidStatuses, true)) {
                $payment->update(['status' => 'paid', 'paid_at' => $payment->paid_at ?: now(), 'raw_callback_json' => $payload]);
                Donation::query()->whereKey($payment->donation_id)->update(['status' => 'paid', 'paid_at' => now()]);
            } elseif (in_array($status, $failedStatuses, true)) {
                $payment->update(['status' => str_contains($status, 'expir') || in_array($status, ['expire', '4'], true) ? 'expired' : 'failed', 'raw_callback_json' => $payload]);
                Donation::query()->whereKey($payment->donation_id)->update(['status' => $payment->status]);
            }

            $webhook->forceFill(['processed_at' => now()])->save();

            return $webhook->fresh();
        });
    }
}
