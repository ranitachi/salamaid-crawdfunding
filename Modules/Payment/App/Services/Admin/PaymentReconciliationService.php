<?php

namespace Modules\Payment\App\Services\Admin;

use Modules\Donation\Entities\Donation;
use Modules\Payment\Entities\Payment;

class PaymentReconciliationService
{
    public function mismatches(): array
    {
        return [
            'payments_without_donation' => Payment::query()
                ->with('donation')
                ->whereDoesntHave('donation')
                ->latest()
                ->get(),
            'paid_payment_not_paid_donation' => Payment::query()
                ->with('donation')
                ->where('status', 'paid')
                ->whereHas('donation', fn ($query) => $query->where('status', '!=', 'paid'))
                ->latest()
                ->get(),
            'paid_donation_without_paid_payment' => Donation::query()
                ->with('payment')
                ->where('status', 'paid')
                ->where(function ($query) {
                    $query->whereDoesntHave('payment')
                        ->orWhereHas('payment', fn ($payment) => $payment->where('status', '!=', 'paid'));
                })
                ->latest()
                ->get(),
        ];
    }

    public function count(): int
    {
        return collect($this->mismatches())->sum(fn ($items) => $items->count());
    }
}
