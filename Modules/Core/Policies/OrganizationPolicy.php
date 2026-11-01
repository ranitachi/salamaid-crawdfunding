<?php

namespace Modules\Core\Policies;

use App\Models\User;
use Modules\Core\Entities\Organization;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyAdminPermission('organizations.view', 'organizations.review');
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->viewAny($user);
    }

    /** Verify, reject, suspend, restore. */
    public function review(User $user, Organization $organization): bool
    {
        return $user->hasAdminPermission('organizations.review');
    }
}
