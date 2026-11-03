<?php

namespace Modules\Transparency\Policies;

use App\Models\User;
use Modules\Transparency\Entities\CampaignUpdate;

class CampaignUpdatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyAdminPermission('transparency.view', 'transparency.review', 'transparency.manage');
    }

    public function view(User $user, CampaignUpdate $update): bool
    {
        return $this->viewAny($user) && $this->belongsToManagedOrganization($user, $update);
    }

    /** Record an update/report on behalf of an organization. */
    public function create(User $user): bool
    {
        return $user->hasAnyAdminPermission('transparency.review', 'transparency.manage');
    }

    /** Submit an owned draft for reviewer approval. */
    public function submit(User $user, CampaignUpdate $update): bool
    {
        return $user->hasAdminPermission('transparency.review')
            || ($user->hasAdminPermission('transparency.manage') && $this->belongsToManagedOrganization($user, $update));
    }

    /** Approve, reject / request revision. */
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
        return $user->hasAdminPermission('transparency.review')
            || ($user->hasAdminPermission('transparency.manage') && $this->belongsToManagedOrganization($user, $update));
    }

    private function belongsToManagedOrganization(User $user, CampaignUpdate $update): bool
    {
        if (! $user->isOrganizationAdmin()) {
            return true;
        }

        return in_array((string) $update->campaign?->organization_id, $user->managedOrganizationIds(), true);
    }
}
