<?php

namespace Modules\Operations\App\Services\Admin;

use App\Contracts\AdminAuditLogger;
use App\Exceptions\AdminActionException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Operations\Entities\Payout;

class PayoutService
{
    public function __construct(
        private readonly PayoutBalanceService $balance,
        private readonly AdminAuditLogger $audit,
    ) {
    }

    public function approve(Payout $payout, User $actor): Payout
    {
        if ((string) $payout->requested_by === (string) $actor->getKey()) {
            throw AdminActionException::requirement('Pengaju payout tidak dapat menyetujui payout yang sama.');
        }

        if ($payout->status !== 'requested') {
            throw AdminActionException::invalidTransition('payout', $payout->status, 'approved');
        }

        $organization = $payout->organization;
        if (! $organization || ! $organization->isVerified()) {
            throw AdminActionException::requirement('Organisasi harus terverifikasi sebelum payout disetujui.');
        }

        return $this->transition($payout, $actor, 'approved', [
            'approved_by' => $actor->getKey(),
        ], null, true);
    }

    public function reject(Payout $payout, User $actor, string $reason): Payout
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw AdminActionException::requirement('Alasan penolakan payout wajib diisi.');
        }

        if ($payout->status !== 'requested') {
            throw AdminActionException::invalidTransition('payout', $payout->status, 'rejected');
        }

        return $this->transition($payout, $actor, 'rejected', ['note' => $reason], $reason);
    }

    public function markPaid(Payout $payout, User $actor, array $evidenceData): Payout
    {
        $evidenceType = $evidenceData['type'] ?? 'url';
        $evidenceValue = $evidenceData['value'] ?? '';

        $evidenceUrl = '';
        if ($evidenceType === 'path') {
            // Private storage path - validate file exists
            if (! storage('private')->exists($evidenceValue)) {
                throw AdminActionException::requirement('File bukti tidak ditemukan di storage private.');
            }
            $evidenceUrl = storage('private')->url($evidenceValue);
        } elseif ($evidenceType === 'url') {
            // Backward compatibility: old URL format
            $evidenceUrl = $evidenceValue;
            // Validate that it's a proper URL
            if (! filter_var($evidenceUrl, FILTER_VALIDATE_URL)) {
                throw AdminActionException::requirement('Format URL bukti tidak valid.');
            }
        } else {
            throw AdminActionException::requirement('Format bukti tidak dikenal.');
        }

        if ($evidenceUrl === '') {
            throw AdminActionException::requirement('Bukti transfer wajib diisi sebelum payout ditandai paid.');
        }

        if ($payout->status !== 'approved') {
            throw AdminActionException::invalidTransition('payout', $payout->status, 'paid');
        }

        return $this->transition($payout, $actor, 'paid', [
            'evidence_url' => $evidenceUrl,
            'evidence_path' => $evidenceType === 'path' ? $evidenceValue : null,
            'evidence_mime' => $this->detectMime($evidenceValue),
            'evidence_size' => $this->detectSize($evidenceValue),
            'paid_at' => now(),
        ]);
    }

    private function detectMime(string $value): ?string
    {
        // Try to detect MIME type from the path/URL
        if (Str::startsWith($value, 'private/')) {
            $ext = Str::after($value, '.');
            $ext = Str::lower($ext);
            if ($ext === 'jpg' || $ext === 'jpeg') {
                return 'image/jpeg';
            }
            if ($ext === 'png') {
                return 'image/png';
            }
            if ($ext === 'pdf') {
                return 'application/pdf';
            }
        }
        // For URLs, return null (we can't reliably detect MIME from URL alone)
        return null;
    }

    private function detectSize(string $value): ?int
    {
        if (Str::startsWith($value, 'private/')) {
            // Try to get file size from storage
            try {
                return (int) storage('private')->size($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    private function transition(
        Payout $payout,
        User $actor,
        string $target,
        array $attributes,
        ?string $reason = null,
        bool $checkBalance = false,
    ): Payout
    {
        $before = $payout->only(['status', 'approved_by', 'paid_at', 'evidence_url', 'note']);

        $updated = DB::transaction(function () use ($payout, $actor, $target, $attributes, $checkBalance) {
            $locked = Payout::query()->whereKey($payout->getKey())->lockForUpdate()->firstOrFail();

            if ($checkBalance && $locked->amount > $this->balance->availableAmount($locked->organization_id, (string) $locked->getKey())) {
                throw AdminActionException::requirement('Saldo tersedia organisasi tidak mencukupi untuk payout ini.');
            }

            $locked->forceFill(array_merge($attributes, ['status' => $target]))->save();

            return $locked->refresh();
        });

        $this->audit->log(
            'payout.'.$target,
            $updated,
            $before,
            $updated->only(['status', 'approved_by', 'paid_at', 'evidence_url', 'note']),
            $reason,
            $actor,
            ['module' => 'Operations', 'entity' => 'payout'],
        );

        return $updated;
    }
}