<?php

namespace App\Queries\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Catalog\Entities\Campaign;
use Modules\Donation\Entities\Donation;

class DonorQuery
{
    public function campaignDonations(Campaign $campaign, Request $request, int $perPage = 20)
    {
        return Donation::query()
            ->with('user:id,name,email,mobile')
            ->where('campaign_id', $campaign->id)
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function organizationDonations(Request $request, User $actor, int $perPage = 25)
    {
        return Donation::query()
            ->with(['user:id,name,email,mobile', 'campaign:id,title,organization_id'])
            ->when($actor->isOrganizationAdmin(), fn ($query) => $query->whereHas(
                'campaign', fn ($campaign) => $campaign->whereIn('organization_id', $actor->managedOrganizationIds())
            ))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->string('q'));
                $query->where(function ($query) use ($term) {
                    $query->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('mobile', 'like', "%{$term}%"))
                        ->orWhereHas('campaign', fn ($campaign) => $campaign->where('title', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
