<?php

namespace Modules\Donation\Policies;

use App\Models\User;
use Modules\Donation\Entities\Donation;

/**
 * Donation status changes only come from the payment flow, so admins get
 * read access only.
 */
class DonationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAdminPermission('donations.view');
    }

    public function view(User $user, Donation $donation): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return ! $user->isOrganizationAdmin()
            || in_array((string) $donation->campaign?->organization_id, $user->managedOrganizationIds(), true);
    }
}
