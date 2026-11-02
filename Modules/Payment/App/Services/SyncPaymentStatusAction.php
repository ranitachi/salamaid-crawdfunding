<?php

namespace Modules\Payment\App\Services;

use App\Contracts\AdminAuditLogger;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\Payment\App\Exceptions\MayarRequestException;
use Modules\Payment\Entities\Payment;
use Modules\Payment\Entities\PaymentSyncLog;

/**
 * Confirms one payment's status with the provider (server-to-server inquiry)
 * and applies it through ApplyPaymentStatusAction. Every attempt is recorded
 * in payment_sync_logs; nothing here lets an admin pick a status manually.
 */
class SyncPaymentStatusAction
{
    public function __construct(
        private readonly MayarClient $client,
        private readonly ApplyPaymentStatusAction $apply,
        private readonly AdminAuditLogger $audit,
    ) {
    }

    /**
     * @param 'manual'|'webhook'|'retry' $trigger
     *
     * @throws MayarRequestException when the provider call fails (already logged)
     */
    public function execute(Payment $payment, string $trigger, ?User $actor = null, int $attempt = 1): PaymentSyncLog
    {
        $base = [
            'payment_id' => $payment->getKey(),
            'provider' => (string) $payment->provider,
            'trigger' => $trigger,
            'attempt' => max(1, $attempt),
            'status_before' => (string) $payment->status,
            'actor_id' => $actor?->getKey(),
        ];

        if ($payment->provider !== 'mayar') {
            return $this->record($base + ['outcome' => 'unsupported', 'error' => 'Inquiry hanya tersedia untuk provider Mayar.']);
        }

        try {
            $data = $this->client->getTransaction((string) $payment->order_id);
        } catch (MayarRequestException $exception) {
            $this->record($base + [
                'outcome' => 'failed',
                'http_status' => $exception->httpStatus,
                'error' => $exception->getMessage(),
            ]);

            Log::warning('payment.sync.failed', [
                'payment_id' => $payment->getKey(),
                'trigger' => $trigger,
                'attempt' => $attempt,
                'http_status' => $exception->httpStatus,
                'retryable' => $exception->retryable,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $providerStatus = is_string($data['status'] ?? null) ? $data['status'] : null;
        $summary = Arr::only($data, ['id', 'status', 'amount', 'paymentMethod', 'updatedAt']);
        $base += ['provider_status' => $providerStatus, 'http_status' => 200, 'response_summary' => $summary];

        if ((string) ($data['id'] ?? '') !== (string) $payment->order_id) {
            return $this->record($base + ['outcome' => 'mismatch', 'error' => 'ID transaksi provider tidak cocok dengan order_id.']);
        }

        $internal = MayarStatusMapper::toInternal($providerStatus);

        if ($internal === null) {
            return $this->record($base + ['outcome' => 'unmapped', 'error' => 'Status provider tidak dikenal; status internal tidak diubah.']);
        }

        $expected = (int) ($payment->donation?->gross_amount ?? 0);

        if ($internal === 'paid' && $expected > 0 && (int) ($data['amount'] ?? 0) < $expected) {
            return $this->record($base + ['outcome' => 'mismatch', 'error' => 'Nominal transaksi provider lebih kecil dari nominal donasi.']);
        }

        $result = $this->apply->execute($payment, $internal);

        $log = $this->record($base + [
            'outcome' => $result['changed'] ? 'updated' : 'unchanged',
            'status_after' => $result['after'],
        ]);

        if ($actor) {
            $this->audit->log(
                'payment.sync',
                $payment,
                ['status' => $result['before']],
                ['status' => $result['after'], 'provider_status' => $providerStatus],
                null,
                $actor,
                ['module' => 'Payment', 'entity' => 'payment', 'trigger' => $trigger, 'outcome' => $log->outcome],
            );
        }

        return $log;
    }

    /** @param array<string, mixed> $attributes */
    private function record(array $attributes): PaymentSyncLog
    {
        $attributes['status_after'] ??= $attributes['status_before'] ?? null;
        $attributes['error'] = isset($attributes['error']) ? mb_substr((string) $attributes['error'], 0, 255) : null;

        $log = new PaymentSyncLog;
        $log->forceFill($attributes + ['created_at' => now()])->save();

        return $log;
    }
}
