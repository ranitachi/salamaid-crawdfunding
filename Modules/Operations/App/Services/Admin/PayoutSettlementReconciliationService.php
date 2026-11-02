<?php

namespace Modules\Operations\App\Services\Admin;

use App\Contracts\AdminAuditLogger;
use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Core\Entities\Organization;
use Modules\Operations\Entities\Payout;
use Modules\Operations\Entities\PayoutRiskFlag;

class PayoutSettlementReconciliationService
{
    public function __construct(private readonly AdminAuditLogger $audit) {}

    /** One aggregate row per organization; avoids balance queries per row. */
    public function organizations(): Collection
    {
        $rows = Organization::query()
            ->select(['organizations.id', 'organizations.name', 'organizations.status'])
            ->selectSub(function ($query) {
                $query->from('donations')
                    ->join('campaigns', 'campaigns.id', '=', 'donations.campaign_id')
                    ->whereColumn('campaigns.organization_id', 'organizations.id')
                    ->where('donations.status', 'paid')
                    ->selectRaw('COALESCE(SUM(donations.net_amount), 0)');
            }, 'settled_amount')
            ->selectSub(function ($query) {
                $query->from('payouts')
                    ->whereColumn('payouts.organization_id', 'organizations.id')
                    ->whereIn('payouts.status', ['requested', 'approved', 'paid'])
                    ->selectRaw('COALESCE(SUM(payouts.amount), 0)');
            }, 'reserved_amount')
            ->selectSub(function ($query) {
                $query->from('payouts')
                    ->whereColumn('payouts.organization_id', 'organizations.id')
                    ->where('payouts.status', 'paid')
                    ->selectRaw('COALESCE(SUM(payouts.amount), 0)');
            }, 'paid_payout_amount')
            ->orderBy('organizations.name')
            ->get();

        return $rows->map(fn ($row) => $this->decorate($row));
    }

    public function forOrganization(string $organizationId): ?object
    {
        return $this->organizations()->firstWhere('id', $organizationId);
    }

    /** Rebuild deterministic flags without storing payment payloads or account data. */
    public function refreshFlags(Payout $payout): Collection
    {
        $organization = $this->forOrganization((string) $payout->organization_id);
        if (! $organization) return collect();

        $definitions = [];
        if ($organization->status !== 'verified') {
            $definitions[] = ['code' => 'organization_not_verified', 'severity' => 'high', 'reason' => 'Organisasi payout tidak berstatus verified.'];
        }
        if ((int) $payout->amount > (int) $organization->available_amount) {
            $definitions[] = ['code' => 'payout_exceeds_available', 'severity' => 'high', 'reason' => 'Nominal payout melebihi saldo settled yang tersedia.'];
        }
        if ((int) $payout->amount >= (int) config('operations.risk.large_payout_amount', 100000000)) {
            $definitions[] = ['code' => 'large_payout', 'severity' => 'medium', 'reason' => 'Nominal payout melewati ambang review manual.'];
        }

        foreach ($definitions as $definition) {
            $flag = $payout->riskFlags()->where('code', $definition['code'])->first();
            if ($flag && $flag->status !== 'open') continue;
            PayoutRiskFlag::updateOrCreate(
                ['payout_id' => $payout->getKey(), 'code' => $definition['code']],
                ['organization_id' => $payout->organization_id, 'severity' => $definition['severity'], 'status' => 'open', 'reason' => $definition['reason'], 'metadata' => ['amount' => (int) $payout->amount]],
            );
        }

        return $payout->riskFlags()->latest()->get();
    }

    public function reviewFlag(PayoutRiskFlag $flag, User $actor, string $status): PayoutRiskFlag
    {
        abort_unless(in_array($status, ['reviewed', 'dismissed', 'confirmed'], true), 422);
        $before = $flag->only(['status', 'reviewed_by', 'reviewed_at']);
        $flag->forceFill(['status' => $status, 'reviewed_by' => $actor->getKey(), 'reviewed_at' => now()])->save();
        $this->audit->log('payout.risk_flag.'.$status, $flag, $before, $flag->only(['status', 'reviewed_by', 'reviewed_at']), null, $actor, ['module' => 'Operations', 'entity' => 'payout_risk_flag']);
        return $flag->refresh();
    }

    private function decorate(object $row): object
    {
        $row->settled_amount = (int) $row->settled_amount;
        $row->reserved_amount = (int) $row->reserved_amount;
        $row->paid_payout_amount = (int) $row->paid_payout_amount;
        $row->available_amount = max(0, $row->settled_amount - $row->reserved_amount);
        $row->has_mismatch = $row->reserved_amount > $row->settled_amount || $row->paid_payout_amount > $row->settled_amount;
        $row->has_verification_issue = $row->status !== 'verified' && $row->reserved_amount > 0;
        return $row;
    }
}
