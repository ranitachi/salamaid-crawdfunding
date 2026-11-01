<?php

namespace Modules\Operations\App\Services\Admin;

use App\Contracts\AdminAuditLogger;
use App\Exceptions\AdminActionException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

    public function markPaid(Payout $payout, User $actor, string $evidenceUrl): Payout
    {
        $evidenceUrl = trim($evidenceUrl);
        if ($evidenceUrl === '') {
            throw AdminActionException::requirement('Bukti transfer wajib diisi sebelum payout ditandai paid.');
        }

        if ($payout->status !== 'approved') {
            throw AdminActionException::invalidTransition('payout', $payout->status, 'paid');
        }

        return $this->transition($payout, $actor, 'paid', [
            'evidence_url' => $evidenceUrl,
            'paid_at' => now(),
        ]);
    }

    private function transition(
        Payout $payout,
        User $actor,
        string $target,
        array $attributes,
        ?string $reason = null,
        bool $checkBalance = false,
    ): Payout {
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
            'payout.'.($target === 'paid' ? 'mark_paid' : $target),
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
