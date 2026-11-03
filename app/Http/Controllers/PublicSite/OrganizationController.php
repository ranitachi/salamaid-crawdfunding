<?php

namespace App\Http\Controllers\PublicSite;

use Illuminate\View\View;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;

class OrganizationController
{
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
