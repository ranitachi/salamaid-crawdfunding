<?php

namespace Modules\Operations\App\Queries\Admin;

use Illuminate\Http\Request;
use Modules\Operations\Entities\Payout;

class PayoutMonitoringQuery
{
    public function paginate(Request $request, int $perPage = 15)
    {
        return Payout::query()
            ->with(['organization:id,name,slug', 'requester:id,name,email', 'approver:id,name,email'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->string('q'));
                $query->where(function ($query) use ($term) {
                    $query->where('payouts.id', $term)
                        ->orWhere('note', 'like', "%{$term}%")
                        ->orWhereHas('organization', fn ($organization) => $organization->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('organization_id'), fn ($query) => $query->where('organization_id', $request->string('organization_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function summary(): array
    {
        return [
            'total' => Payout::query()->count(),
            'requested' => Payout::query()->where('status', 'requested')->count(),
            'approved' => Payout::query()->where('status', 'approved')->count(),
            'pending_amount' => Payout::query()->whereIn('status', ['requested', 'approved'])->sum('amount'),
        ];
    }
}
