<?php

namespace Modules\Payment\App\Queries\Admin;

use Illuminate\Http\Request;
use Modules\Payment\Entities\Payment;
use Modules\Payment\Entities\Webhook;

class PaymentMonitoringQuery
{
    public function paginate(Request $request, int $perPage = 15)
    {
        return $this->forExport($request)
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function forExport(Request $request)
    {
        return Payment::query()
            ->with(['donation:id,campaign_id,user_id,gross_amount,status', 'donation.campaign:id,title', 'donation.user:id,name,email'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->string('q'));

                $query->where(function ($query) use ($term) {
                    $query->where('payments.id', $term)
                        ->orWhere('order_id', $term)
                        ->orWhere('channel_code', $term)
                        ->orWhereHas('donation', fn ($donation) => $donation->where('id', $term));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('provider'), fn ($query) => $query->where('provider', $request->string('provider')))
            ->when($request->filled('channel_code'), fn ($query) => $query->where('channel_code', $request->string('channel_code')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')));
    }

    public function webhookPaginate(Request $request, int $perPage = 15)
    {
        return Webhook::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->string('q'));
                $query->where(function ($query) use ($term) {
                    $query->where('order_id', $term)
                        ->orWhere('provider', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('provider'), fn ($query) => $query->where('provider', $request->string('provider')))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function summary(): array
    {
        return [
            'total' => Payment::query()->count(),
            'paid' => Payment::query()->where('status', 'paid')->count(),
            'pending' => Payment::query()->where('status', 'pending')->count(),
            'failed' => Payment::query()->where('status', 'failed')->count(),
        ];
    }
}
