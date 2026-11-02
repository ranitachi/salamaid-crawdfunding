<?php

namespace Modules\Payment\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Payment\App\Exceptions\MayarRequestException;
use Modules\Payment\Entities\Payment;
use Modules\Payment\Entities\Webhook;

/**
 * Processes an already recorded (verified) webhook. Idempotent: a processed
 * webhook is never applied twice, and status changes go through the
 * monotonic ApplyPaymentStatusAction.
 *
 * Mayar: the payload is only a trigger. `data.status` there is a delivery
 * status ("SUCCESS" also appears on payment.reminder for unpaid transactions),
 * so the final status always comes from a server-to-server inquiry.
 */
class ProcessWebhookAction
{
    public function __construct(
        private readonly SyncPaymentStatusAction $sync,
        private readonly ApplyPaymentStatusAction $apply,
    ) {
    }

    /**
     * @throws \Modules\Payment\App\Exceptions\MayarRequestException retryable provider failures bubble up to the job
     */
    public function process(Webhook $webhook, int $attempt = 1): Webhook
    {
        $webhook->refresh();

        if ($webhook->processed_at) {
            return $webhook;
        }

        $webhook->forceFill(['attempts' => $attempt])->save();

        $payment = Payment::query()->where('order_id', $webhook->order_id)->first();

        if (! $payment) {
            // Not ours (or not created yet). Keep it unprocessed for a manual retry; no automatic loop.
            $webhook->forceFill(['last_error' => 'Payment untuk order_id ini tidak ditemukan.'])->save();

            return $webhook;
        }

        try {
            if ($webhook->provider === 'mayar') {
                $log = $this->sync->execute($payment, 'webhook', null, $attempt);

                // A "payment received" event the provider does not confirm yet: re-check with backoff.
                if ($webhook->event === 'payment.received' && $log->status_after === 'pending') {
                    throw new MayarRequestException('Mayar belum mengonfirmasi pembayaran; inquiry akan diulang.', 200, true);
                }
            } else {
                $this->applyPayloadStatus($webhook, $payment);
            }
        } catch (\Throwable $exception) {
            $webhook->forceFill(['last_error' => mb_substr($exception->getMessage(), 0, 255)])->save();

            throw $exception;
        }

        return DB::transaction(function () use ($webhook) {
            /** @var Webhook $locked */
            $locked = Webhook::query()->whereKey($webhook->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->processed_at) {
                $locked->forceFill(['processed_at' => now(), 'last_error' => null, 'failed_at' => null])->save();
            }

            return $locked;
        });
    }

    /** Signed providers (generic/faspay) whose payload status is authoritative. */
    private function applyPayloadStatus(Webhook $webhook, Payment $payment): void
    {
        $settings = (array) config("payment.webhooks.providers.{$webhook->provider}", []);
        $status = strtolower((string) data_get($webhook->payload, $settings['status_field'] ?? 'status', ''));
        $paid = array_map('strtolower', config('payment.webhooks.paid_statuses', []));
        $failed = array_map('strtolower', config('payment.webhooks.failed_statuses', []));

        $target = match (true) {
            in_array($status, $paid, true) => 'paid',
            in_array($status, $failed, true) => str_contains($status, 'expir') || $status === '4' ? 'expired' : 'failed',
            default => null,
        };

        if ($target) {
            $this->apply->execute($payment, $target, (array) $webhook->payload);
        }
    }
}
