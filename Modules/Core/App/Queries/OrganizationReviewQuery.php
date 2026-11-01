<?php

namespace Modules\Core\App\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Entities\Organization;

class OrganizationReviewQuery
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Organization::query()
            ->withCount(['members', 'campaigns'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('contact_email', 'like', "%{$search}%")
                        ->orWhere('contact_phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['location'] ?? null, function ($query, $location) {
                $query->where('address', 'like', "%{$location}%");
            })
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function detail(Organization $organization): Organization
    {
        return $organization->load([
            'members' => fn ($query) => $query->orderBy('role')->orderBy('created_at'),
            'members.user',
            'campaigns' => fn ($query) => $query->with('category')->latest('created_at'),
            'reviewer',
        ])->loadCount(['members', 'campaigns']);
    }
}
