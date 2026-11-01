<?php

namespace Modules\Transparency\Policies;

use App\Models\User;
use Modules\Transparency\Entities\CampaignUpdate;

class CampaignUpdatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAdminPermission('transparency.review');
    }

    public function view(User $user, CampaignUpdate $update): bool
    {
        return $this->viewAny($user);
    }

    /** Approve, request revision, publish, unpublish. */
    public function review(User $user, CampaignUpdate $update): bool
    {
        return $user->hasAdminPermission('transparency.review');
    }
}
