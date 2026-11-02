<?php

namespace Modules\Operations\Policies;

use App\Models\User;
use Modules\Operations\Entities\Payout;

class PayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyAdminPermission('payouts.view', 'payouts.manage');
    }

    public function view(User $user, Payout $payout): bool
    {
        return $this->viewAny($user);
    }

    public function approve(User $user, Payout $payout): bool
    {
        return $user->hasAdminPermission('payouts.manage')
            && (string) $payout->requested_by !== (string) $user->getKey();
    }

    public function reject(User $user, Payout $payout): bool
    {
        return $user->hasAdminPermission('payouts.manage');
    }

    public function markPaid(User $user, Payout $payout): bool
    {
        return $user->hasAdminPermission('payouts.manage');
    }

    /**
     * Transfer evidence can contain bank details: only roles that manage payouts
     * (finance, super_admin) may download it. View-only roles see metadata only.
     */
    public function downloadEvidence(User $user, Payout $payout): bool
    {
        return $user->hasAdminPermission('payouts.manage') && $payout->hasPrivateEvidence();
    }
}
