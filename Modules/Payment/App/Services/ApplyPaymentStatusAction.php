<?php

namespace Modules\Payment\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Donation\Entities\Donation;
use Modules\Payment\Entities\Payment;

/**
 * Single place that moves a payment (and its donation) to a provider-confirmed
 * status. Idempotent and monotonic:
 * - `paid` is terminal and is never downgraded;
 * - `pending` never overrides a settled status;
 * - pending/expired/failed → paid is allowed (late payment confirmed by provider).
 */
class ApplyPaymentStatusAction
{
    public const STATUSES = ['pending', 'paid', 'expired', 'failed'];

    /**
     * @param array<string, mixed>|null $callbackPayload stored as raw_callback_json when given
     * @return array{changed: bool, before: string, after: string}
     */
    public function execute(Payment $payment, string $target, ?array $callbackPayload = null): array
    {
        if (! in_array($target, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Status payment tidak dikenal: {$target}");
        }

        return DB::transaction(function () use ($payment, $target, $callbackPayload) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            $before = (string) $locked->status;

            if (! $this->allowed($before, $target)) {
                return ['changed' => false, 'before' => $before, 'after' => $before];
            }

            $attributes = ['status' => $target];

            if ($target === 'paid') {
                $attributes['paid_at'] = $locked->paid_at ?? now();
            }

            if ($callbackPayload !== null) {
                $attributes['raw_callback_json'] = $callbackPayload;
            }

            $locked->forceFill($attributes)->save();

            $donation = Donation::query()->whereKey($locked->donation_id)->lockForUpdate()->first();

            if ($donation && $donation->status !== 'paid' && $donation->status !== 'refund') {
                $donation->forceFill(array_filter([
                    'status' => $target,
                    'paid_at' => $target === 'paid' ? ($donation->paid_at ?? now()) : null,
                ], fn ($value) => $value !== null))->save();
            }

            $payment->setRawAttributes($locked->getAttributes(), true);

            return ['changed' => true, 'before' => $before, 'after' => $target];
        });
    }

    private function allowed(string $from, string $to): bool
    {
        return match ($from) {
            'pending' => $to !== 'pending',
            'expired', 'failed' => $to === 'paid',
            default => false, // paid (terminal) or unknown
        };
    }
}
