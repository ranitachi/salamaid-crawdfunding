<?php

namespace Modules\Core\App\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Entities\Category;

class CategoryManagementQuery
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Category::query()
            ->withCount(['campaigns' => fn ($query) => $query->withTrashed()])
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }
}
