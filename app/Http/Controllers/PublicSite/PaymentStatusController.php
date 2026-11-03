<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Modules\Donation\Entities\Donation;
use Modules\Payment\App\Exceptions\MayarRequestException;
use Modules\Payment\App\Services\SyncPaymentStatusAction;

class PaymentStatusController extends Controller
{
    public function show(Request $request, Donation $donation, SyncPaymentStatusAction $sync): View
    {
        $this->authorizeAccess($request, $donation);
        $donation->load(['payment', 'campaign:id,title,slug']);

        // Mayar redirects the browser to this signed URL after checkout. The
        // redirect itself is not payment proof, so confirm the status with
        // Mayar before rendering the result page.
        if (URL::hasValidSignature($request) && $donation->payment?->provider === 'mayar' && $donation->payment->status === 'pending') {
            try {
                $sync->execute($donation->payment, 'callback');
                $donation->refresh()->load(['payment', 'campaign:id,title,slug']);
            } catch (MayarRequestException $exception) {
                report($exception);
            }
        }

        return view('public.payments.status', compact('donation'));
    }

    public function refresh(Request $request, Donation $donation, SyncPaymentStatusAction $sync): RedirectResponse
    {
        $this->authorizeAccess($request, $donation);
        $donation->load('payment');

        if ($donation->payment?->provider !== 'mayar' || $donation->payment->status !== 'pending') {
            return back()->with('status_message', 'Status pembayaran belum perlu disinkronkan.');
        }

        try {
            $sync->execute($donation->payment, 'manual');
            return back()->with('status_message', 'Status pembayaran sudah diperiksa ulang ke Mayar.');
        } catch (MayarRequestException $exception) {
            report($exception);
            return back()->with('status_error', 'Status Mayar belum dapat diperiksa. Silakan coba lagi.');
        }
    }

    private function authorizeAccess(Request $request, Donation $donation): void
    {
        $ownedByUser = auth()->check() && (string) $donation->user_id === (string) auth()->id();
        abort_unless($ownedByUser || URL::hasValidSignature($request), 403);
    }
}
