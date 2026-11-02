<?php

namespace App\Http\Controllers\PublicSite;

use App\Queries\PublicSite\CampaignQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController
{
    public function __construct(private readonly CampaignQuery $campaigns) {}

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
}
