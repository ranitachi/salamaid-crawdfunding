<?php

namespace Modules\Catalog\App\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\User;
use Modules\Catalog\Entities\Campaign;

class CampaignModerationQuery
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15, ?User $actor = null): LengthAwarePaginator
    {
        return Campaign::query()
            ->with([
                'organization:id,name,slug,status',
                'category:id,name,slug',
            ])
            ->withCount('donations')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category_id'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->when($filters['organization_id'] ?? null, fn ($query, $organization) => $query->where('organization_id', $organization))
            ->when(array_key_exists('featured', $filters) && $filters['featured'] !== null, function ($query) use ($filters) {
                $query->where('is_featured', (bool) $filters['featured']);
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('location_label', 'like', "%{$search}%");
                });
            })
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($actor?->isOrganizationAdmin(), fn ($query) => $query->whereIn('organization_id', $actor->managedOrganizationIds()))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function detail(Campaign $campaign): Campaign
    {
        return $campaign->load([
            'organization',
            'category',
            'media' => fn ($query) => $query->orderBy('sort')->orderBy('created_at'),
            'reviewer',
        ])
            ->loadCount('donations')
            ->loadSum(['donations as paid_donation_total' => fn ($query) => $query->where('status', 'paid')], 'net_amount');
    }
}
