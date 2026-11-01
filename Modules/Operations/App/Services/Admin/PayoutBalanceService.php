<?php

namespace Modules\Operations\App\Services\Admin;

use Modules\Donation\Entities\Donation;
use Modules\Operations\Entities\Payout;

class PayoutBalanceService
{
    public function settledAmount(string $organizationId): int
    {
        return (int) Donation::query()
            ->where('status', 'paid')
            ->whereHas('campaign', fn ($campaign) => $campaign->where('organization_id', $organizationId))
            ->sum('net_amount');
    }

    public function reservedAmount(string $organizationId, ?string $exceptPayoutId = null): int
    {
        return (int) Payout::query()
            ->where('organization_id', $organizationId)
            ->whereIn('status', ['requested', 'approved', 'paid'])
            ->when($exceptPayoutId, fn ($query) => $query->whereKeyNot($exceptPayoutId))
            ->sum('amount');
    }

    public function availableAmount(string $organizationId, ?string $exceptPayoutId = null): int
    {
        return max(0, $this->settledAmount($organizationId) - $this->reservedAmount($organizationId, $exceptPayoutId));
    }
}
