<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function updateRole(User $actor, User $target): bool
    {
        return $actor->hasAdminPermission('users.roles.manage');
    }
}
