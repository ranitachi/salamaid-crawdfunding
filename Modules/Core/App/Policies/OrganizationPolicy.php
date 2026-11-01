<?php

namespace Modules\Core\App\Policies;

use App\Models\User;
use App\Support\AdminPermissions;
use Modules\Core\Entities\Organization;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return AdminPermissions::allowedAny($user, [
            'organizations.review',
            'organizations.view',
        ]);
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->viewAny($user);
    }

    public function review(User $user, Organization $organization): bool
    {
        return AdminPermissions::allowed($user, 'organizations.review');
    }

    public function viewSensitive(User $user, Organization $organization): bool
    {
        return AdminPermissions::allowed($user, 'organizations.review');
    }
}
