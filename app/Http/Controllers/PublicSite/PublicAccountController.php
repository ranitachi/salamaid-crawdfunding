<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Queries\PublicSite\CampaignQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Donation\Entities\Donation;
use Modules\Payment\App\Exceptions\MayarRequestException;

class PublicAccountController extends Controller
{
    public function __construct(private readonly CampaignQuery $campaigns)
    {
    }

    public function account(Request $request): View
    {
        return view('public.account', ['user' => $request->user(), 'unreadCount' => $request->user()->unreadNotifications()->count()]);
    }

    public function donations(Request $request): View
    {
        $donations = Donation::query()->where('user_id', $request->user()->getKey())->with(['campaign:id,title,slug', 'payment:id,donation_id,provider,order_id,status,paid_at'])->latest()->paginate(12)->withQueryString();
        return view('public.donations', compact('donations'));
    }

    public function receipt(Request $request, Donation $donation): View
    {
        abort_unless((string) $donation->user_id === (string) $request->user()->getKey(), 404);
        $donation->load(['campaign:id,title,slug', 'payment']);
        return view('public.receipt', compact('donation'));
    }

    public function regeneratePayment(Request $request, Donation $donation, \App\Services\PublicDonationCheckoutService $checkout): RedirectResponse
    {
        abort_unless((string) $donation->user_id === (string) $request->user()->getKey(), 404);
        $validated = $request->validate(['mobile' => ['nullable', 'string', 'regex:/^\+?[0-9 ()-]{8,24}$/']]);

        try {
            $checkout->regenerate($donation, $validated['mobile'] ?? null);
            return to_route('public.receipt', $donation)->with('status_message', 'Link pembayaran baru berhasil dibuat.');
        } catch (MayarRequestException|\RuntimeException $exception) {
            report($exception);
            return to_route('public.receipt', $donation)->with('status_error', 'Link pembayaran belum dapat dibuat. Silakan coba lagi.');
        }
    }

    public function inbox(Request $request): View
    {
        return view('public.inbox', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20)->withQueryString(),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
            'initiatives' => $this->campaigns->urgent(2),
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();
        return back()->with('status_message', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return back()->with('status_message', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
