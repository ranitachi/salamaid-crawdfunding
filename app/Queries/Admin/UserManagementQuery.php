<?php

namespace App\Queries\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Entities\OrganizationMember;

class UserManagementQuery
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, OrganizationMember>
     */
    public function memberships(User $user): Collection
    {
        return OrganizationMember::query()
            ->with('organization:id,name,slug,status')
            ->where('user_id', $user->getKey())
            ->orderBy('created_at')
            ->get();
    }
}
