<?php

namespace Modules\Catalog\Policies;

use App\Models\User;
use Modules\Catalog\Entities\Campaign;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyAdminPermission('campaigns.view', 'campaigns.review');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $this->viewAny($user);
    }

    /** Approve/reject a submitted campaign. */
    public function review(User $user, Campaign $campaign): bool
    {
        return $user->hasAdminPermission('campaigns.review');
    }

    /** Publish, suspend, feature, close. */
    public function publish(User $user, Campaign $campaign): bool
    {
        return $user->hasAdminPermission('campaigns.publish');
    }
}
