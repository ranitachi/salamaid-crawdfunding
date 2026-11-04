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

    public function home(Request $request): View
    {
        $featured = $this->campaigns->featuredPage(3, max(1, $request->integer('featured_page', 1)));

        return view('public.home', [
            'featured' => $featured,
            'heroCampaign' => $featured->first(),
            'categories' => $this->campaigns->categories(),
            'programs' => $this->campaigns->programsPage(6, max(1, $request->integer('program_page', 1))),
        ]);
    }

    public function program(Request $request): View
    {
        $category = $request->string('category')->toString() ?: null;

        return view('public.program', [
            'campaigns' => $this->campaigns->paginate($request),
            'categories' => $this->campaigns->categories(),
            'featured' => $this->campaigns->featured(category: $category),
            'urgent' => $this->campaigns->urgent(category: $category),
        ]);
    }

    public function show(string $slug): View
    {
        $campaign = $this->campaigns->findPublished($slug);
        abort_if($campaign === null, 404);
        return view('public.campaigns.show', [
            'campaign' => $campaign,
            'description' => str($campaign->description)->squish()->limit(160),
            'ogImage' => $campaign->cover_url,
        ]);
    }

    public function transparency(string $slug): View
    {
        $campaign = $this->campaigns->findPublished($slug);
        abort_if($campaign === null, 404);

        $updates = $campaign->updates;
        $reports = $updates->filter(fn ($update) => $update->isFundReport())->values();

        return view('public.campaigns.transparency', [
            'campaign' => $campaign,
            'updates' => $updates,
            'reports' => $reports,
            'reportedAmount' => (int) $reports->sum('amount_used'),
            'description' => 'Kabar terbaru dan laporan alokasi dana untuk '.$campaign->title.'.',
            'ogImage' => $campaign->cover_url,
        ]);
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
