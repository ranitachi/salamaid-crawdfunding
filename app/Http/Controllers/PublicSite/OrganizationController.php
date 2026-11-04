<?php

namespace App\Http\Controllers\PublicSite;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;

class OrganizationController
{
    public function index(Request $request): View
    {
        $organizations = Organization::query()
            ->where('status', 'verified')
            ->whereHas('campaigns', fn ($query) => $query->published())
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = trim((string) $request->string('q'));
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('tagline', 'like', "%{$term}%");
                });
            })
            ->withCount(['campaigns as published_campaign_count' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('public.organizations.index', compact('organizations'));
    }

    public function show(string $slug): View
    {
        $organization = Organization::query()
            ->where('slug', $slug)
            ->where('status', 'verified')
            ->firstOrFail();

        $campaigns = Campaign::query()
            ->published()
            ->where('organization_id', $organization->id)
            ->with(['category:id,name,slug,icon'])
            ->withSum(['donations as paid_amount' => fn ($query) => $query->where('status', 'paid')], 'net_amount')
            ->withCount(['donations as paid_donation_count' => fn ($query) => $query->where('status', 'paid')])
            ->latest('published_at')
            ->get();

        $impact = [
            'amount' => (int) Donation::query()->where('status', 'paid')->whereHas('campaign', fn ($query) => $query->where('organization_id', $organization->id))->sum('net_amount'),
            'count' => (int) Donation::query()->where('status', 'paid')->whereHas('campaign', fn ($query) => $query->where('organization_id', $organization->id))->count(),
        ];

        return view('public.organizations.show', compact('organization', 'campaigns', 'impact'));
    }
}
