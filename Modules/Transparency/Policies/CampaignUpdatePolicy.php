<?php

namespace Modules\Transparency\Policies;

use App\Models\User;
use Modules\Transparency\Entities\CampaignUpdate;

class CampaignUpdatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyAdminPermission('transparency.view', 'transparency.review');
    }

    public function view(User $user, CampaignUpdate $update): bool
    {
        return $this->viewAny($user);
    }

    /** Record an update/report on behalf of an organization. */
    public function create(User $user): bool
    {
        return $user->hasAdminPermission('transparency.review');
    }

    /** Submit, approve, reject / request revision. */
    public function review(User $user, CampaignUpdate $update): bool
    {
        return $user->hasAdminPermission('transparency.review');
    }

    /** Publish and unpublish public content. */
    public function publish(User $user, CampaignUpdate $update): bool
    {
        return $user->hasAdminPermission('transparency.review');
    }

    /** Upload / remove evidence files. */
    public function manageAttachments(User $user, CampaignUpdate $update): bool
    {
        return $user->hasAdminPermission('transparency.review');
    }
}
