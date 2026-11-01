<?php

namespace Modules\Core\App\Policies;

use App\Models\User;
use Modules\Core\Entities\Category;

class CategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasAdminPermission('categories.manage');
    }

    public function view(User $actor, Category $category): bool
    {
        return $actor->hasAdminPermission('categories.manage');
    }

    public function create(User $actor): bool
    {
        return $actor->hasAdminPermission('categories.manage');
    }

    public function update(User $actor, Category $category): bool
    {
        return $actor->hasAdminPermission('categories.manage');
    }

    public function delete(User $actor, Category $category): bool
    {
        return $actor->hasAdminPermission('categories.manage');
    }
}
