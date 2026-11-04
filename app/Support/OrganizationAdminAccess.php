<?php

namespace App\Support;

use App\Models\User;

class OrganizationAdminAccess
{
    public static function canAccess(User $user): bool
    {
        if ($user->role !== 'organization_admin') {
            return true;
        }

        return $user->organizationMemberships()
            ->whereIn('role', ['owner', 'admin'])
            ->whereHas('organization', fn ($organization) => $organization->where('status', 'verified'))
            ->exists();
    }
}
