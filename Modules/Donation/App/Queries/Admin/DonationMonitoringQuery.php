<?php

namespace Modules\Donation\App\Queries\Admin;

use Illuminate\Http\Request;
use App\Models\User;
use Modules\Donation\Entities\Donation;

class DonationMonitoringQuery
{
    public function paginate(Request $request, int $perPage = 15)
    {
        return $this->forExport($request, $request->user())
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function forExport(Request $request, ?User $actor = null)
    {
        return Donation::query()
            ->with(['campaign:id,title,slug,organization_id', 'user:id,name,email,mobile', 'payment:id,donation_id,order_id,status,provider,paid_at'])
            ->when($actor?->isOrganizationAdmin(), fn ($query) => $query->whereHas(
                'campaign', fn ($campaign) => $campaign->whereIn('organization_id', $actor->managedOrganizationIds())
            ))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->string('q'));

                $query->where(function ($query) use ($term) {
                    $query->where('donations.id', $term)
                        ->orWhere('ref_code', $term)
                        ->orWhere('message', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%"))
                        ->orWhereHas('campaign', fn ($campaign) => $campaign->where('title', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('method'), fn ($query) => $query->where('method', $request->string('method')))
            ->when($request->filled('anonymous'), fn ($query) => $query->where('anonymous', $request->boolean('anonymous')))
            ->when($request->filled('campaign_id'), fn ($query) => $query->where('campaign_id', $request->string('campaign_id')))
            ->when($request->filled('amount_min'), fn ($query) => $query->where('gross_amount', '>=', (int) $request->input('amount_min')))
            ->when($request->filled('amount_max'), fn ($query) => $query->where('gross_amount', '<=', (int) $request->input('amount_max')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')));
    }

    public function summary(?User $actor = null): array
    {
        $query = Donation::query()->when($actor?->isOrganizationAdmin(), fn ($builder) => $builder->whereHas(
            'campaign', fn ($campaign) => $campaign->whereIn('organization_id', $actor->managedOrganizationIds())
        ));

        return [
            'total' => (clone $query)->count(),
            'paid' => (clone $query)->where('status', 'paid')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'gross_paid' => (clone $query)->where('status', 'paid')->sum('gross_amount'),
        ];
    }
}
