<?php

namespace Modules\Payment\App\Services;

/**
 * Explicit Mayar V2 transaction status → internal payment status.
 * Unknown values return null and never change a payment.
 */
final class MayarStatusMapper
{
    public const MAP = [
        'paid' => 'paid',
        'unpaid' => 'pending',
        'created' => 'pending',
        'expired' => 'expired',
    ];

    public static function toInternal(?string $providerStatus): ?string
    {
        return self::MAP[strtolower(trim((string) $providerStatus))] ?? null;
    }
}
