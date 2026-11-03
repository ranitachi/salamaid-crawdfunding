<?php

namespace App\Queries\PublicSite;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Entities\Campaign;
use Modules\Donation\Entities\Donation;
use Modules\Core\Entities\Category;

class CampaignQuery
{
    public function featured(int $limit = 3): Collection
    {
        if (! $this->tablesAvailable()) return collect();

        return $this->base()->where('is_featured', true)->latest('featured_at')->limit($limit)->get();
    }

    public function urgent(int $limit = 3): Collection
    {
        if (! $this->tablesAvailable()) return collect();

        return $this->base()
            ->whereNotNull('deadline_at')
            ->orderBy('deadline_at')
            ->limit($limit)
            ->get();
    }

    public function programs(int $limit = 6): Collection
    {
        if (! $this->tablesAvailable()) return collect();

        return $this->base()->latest('published_at')->limit($limit)->get();
    }

    public function paginate(Request $request, int $perPage = 12)
    {
        if (! $this->tablesAvailable()) {
            return new LengthAwarePaginator([], 0, $perPage, 1, ['path' => $request->url()]);
        }

        return $this->base()
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = trim((string) $request->string('q'));
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('campaigns.title', 'like', "%{$term}%")
                        ->orWhere('campaigns.description', 'like', "%{$term}%")
                        ->orWhereHas('organization', fn (Builder $organization) => $organization->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->whereHas('category', fn (Builder $category) => $category->where('slug', $request->string('category'))))
            ->latest('published_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function categories(): Collection
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('campaigns')) return collect();

        return Category::query()->active()->whereHas('campaigns', fn (Builder $campaign) => $campaign->where('status', 'published'))->orderBy('name')->get(['id', 'name', 'slug', 'icon']);
    }

    /** @return array{amount: int, count: int} */
    public function impactSummary(): array
    {
        if (! Schema::hasTable('donations')) {
            return ['amount' => 0, 'count' => 0];
        }

        $paid = Donation::query()->where('status', 'paid');

        return [
            'amount' => (int) (clone $paid)->sum('net_amount'),
            'count' => (int) $paid->count(),
        ];
    }

    public function findPublished(string $slug): ?Campaign
    {
        if (! $this->tablesAvailable()) return null;

        return Campaign::query()
            ->published()
            ->where('slug', $slug)
            ->whereHas('organization', fn (Builder $organization) => $organization->where('status', 'verified'))
            ->with([
                'organization:id,name,slug,status,logo_url,banner_url,tagline,primary_color,secondary_color,description,address,contact_email,contact_phone,website_url,instagram_url,facebook_url,whatsapp_url',
                'category:id,name,slug,icon',
                'media' => fn ($media) => $media->orderBy('sort'),
                'updates' => fn ($updates) => $updates->public()->with('attachments')->latest('published_at'),
            ])
            ->withSum(['donations as paid_amount' => fn (Builder $donation) => $donation->where('status', 'paid')], 'net_amount')
            ->withCount(['donations as paid_donation_count' => fn (Builder $donation) => $donation->where('status', 'paid')])
            ->first();
    }

    private function tablesAvailable(): bool
    {
        return Schema::hasTable('campaigns') && Schema::hasTable('organizations') && Schema::hasTable('donations');
    }

    private function base(): Builder
    {
        return Campaign::query()
            ->published()
            ->whereHas('organization', fn (Builder $organization) => $organization->where('status', 'verified'))
            ->with(['organization:id,name,slug,status,logo_url', 'category:id,name,slug,icon'])
            ->withSum(['donations as paid_amount' => fn (Builder $donation) => $donation->where('status', 'paid')], 'net_amount')
            ->withCount(['donations as paid_donation_count' => fn (Builder $donation) => $donation->where('status', 'paid')]);
    }
}
