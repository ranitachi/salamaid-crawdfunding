<?php

namespace App\Http\Controllers\PublicSite;

use App\Queries\PublicSite\CampaignQuery;
use App\Http\Requests\PublicDonationRequest;
use App\Services\PublicDonationCheckoutService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController
{
    public function __construct(private readonly CampaignQuery $campaigns, private readonly PublicDonationCheckoutService $checkout) {}

    public function home(): View
    {
        return view('public.home', ['featured' => $this->campaigns->featured(), 'categories' => $this->campaigns->categories()]);
    }

    public function program(Request $request): View
    {
        return view('public.program', ['campaigns' => $this->campaigns->paginate($request), 'categories' => $this->campaigns->categories()]);
    }

    public function show(string $slug): View
    {
        $campaign = $this->campaigns->findPublished($slug);
        abort_if($campaign === null, 404);
        return view('public.campaigns.show', compact('campaign'));
    }

    public function donate(string $slug): View
    {
        $campaign = $this->checkout->campaign($slug);
        abort_if($campaign === null, 404);
        return view('public.campaigns.donate', ['campaign' => $campaign, 'channels' => $this->checkout->channels()]);
    }

    public function storeDonation(PublicDonationRequest $request, string $slug)
    {
        $campaign = $this->checkout->campaign($slug);
        abort_if($campaign === null, 404);

        try {
            $result = $this->checkout->create($campaign, $request->validated());
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', 'Pembayaran belum dapat dibuat. Silakan coba lagi.');
        }

        return redirect()->away($result['payment']->redirect_url);
    }
}
