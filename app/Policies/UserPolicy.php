<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasAdminPermission('users.roles.manage');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->hasAdminPermission('users.roles.manage');
    }

    public function updateRole(User $actor, User $target): bool
    {
        return $actor->hasAdminPermission('users.roles.manage');
    }

    public function activate(User $actor, User $target): bool
    {
        return $actor->hasAdminPermission('users.roles.manage');
    }

    public function deactivate(User $actor, User $target): bool
    {
        return $actor->hasAdminPermission('users.roles.manage');
    }

    public function sendPasswordResetLink(User $actor, User $target): bool
    {
        return $actor->hasAdminPermission('users.roles.manage');
    }
}
