<?php

namespace App\Http\Controllers\PublicSite;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Entities\Campaign;

class PublicSeoController
{
    public function sitemap(): Response
    {
        $urls = [
            ['loc' => route('public.home'), 'lastmod' => now()->toDateString()],
            ['loc' => route('public.program'), 'lastmod' => now()->toDateString()],
        ];

        if (Schema::hasTable('campaigns') && Schema::hasTable('organizations')) {
            Campaign::query()
                ->published()
                ->whereHas('organization', fn ($query) => $query->where('status', 'verified'))
                ->orderBy('updated_at')
                ->get(['slug', 'updated_at'])
                ->each(function (Campaign $campaign) use (&$urls): void {
                    $urls[] = ['loc' => route('public.campaigns.show', $campaign->slug), 'lastmod' => $campaign->updated_at?->toDateString()];
                    $urls[] = ['loc' => route('public.campaigns.transparency', $campaign->slug), 'lastmod' => $campaign->updated_at?->toDateString()];
                });
        }

        return response()->view('public.seo.sitemap', compact('urls'), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        return response("User-agent: *\nDisallow: /admin\nDisallow: /account\nDisallow: /inbox\nSitemap: ".route('public.sitemap')."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
