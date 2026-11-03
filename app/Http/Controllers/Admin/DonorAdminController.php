<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Queries\Admin\DonorQuery;
use Illuminate\Http\Request;
use Modules\Catalog\Entities\Campaign;
use Modules\Donation\Entities\Donation;

class DonorAdminController extends Controller
{
    public function __construct(private readonly DonorQuery $query)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Donation::class);

        return view('admin.donors.index', [
            'donations' => $this->query->organizationDonations($request, $request->user()),
        ]);
    }

    public function campaign(Campaign $campaign, Request $request)
    {
        $this->authorize('view', $campaign);

        return view('admin.donors.campaign', [
            'campaign' => $campaign->load('organization:id,name'),
            'donations' => $this->query->campaignDonations($campaign, $request),
        ]);
    }
}
