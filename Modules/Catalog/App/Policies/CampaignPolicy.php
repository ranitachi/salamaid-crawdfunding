<?php

namespace Modules\Catalog\App\Policies;

use App\Models\User;
use App\Support\AdminPermissions;
use Modules\Catalog\Entities\Campaign;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return AdminPermissions::allowedAny($user, [
            'campaigns.review',
            'campaigns.view',
        ]);
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $this->viewAny($user) && $this->belongsToActorOrganization($user, $campaign);
    }

    public function moderate(User $user, Campaign $campaign): bool
    {
        return AdminPermissions::allowed($user, 'campaigns.review');
    }

    public function create(User $user): bool
    {
        return $user->hasAdminPermission('campaigns.manage');
    }

    private function belongsToActorOrganization(User $user, Campaign $campaign): bool
    {
        return ! $user->isOrganizationAdmin() || in_array((string) $campaign->organization_id, $user->managedOrganizationIds(), true);
    }

    /** Compatibility ability used by the admin policy contract. */
    public function review(User $user, Campaign $campaign): bool
    {
        return $this->moderate($user, $campaign);
    }

    /** Publish, suspend, feature, and close remain restricted to publishers. */
    public function publish(User $user, Campaign $campaign): bool
    {
        return AdminPermissions::allowed($user, 'campaigns.publish');
    }

    public function feature(User $user, Campaign $campaign): bool
    {
        return $this->moderate($user, $campaign);
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $this->moderate($user, $campaign);
    }

    public function forceDelete(User $user, Campaign $campaign): bool
    {
        return $this->moderate($user, $campaign);
    }
}
