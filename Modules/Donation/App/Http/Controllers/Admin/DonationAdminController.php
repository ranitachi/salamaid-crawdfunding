<?php

namespace Modules\Donation\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Donation\App\Queries\Admin\DonationMonitoringQuery;
use Modules\Donation\App\Services\Admin\DonationExportService;
use Modules\Donation\Entities\Donation;

class DonationAdminController extends Controller
{
    public function __construct(
        private readonly DonationMonitoringQuery $query,
        private readonly DonationExportService $export,
    )
    {
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Donation::class);

        return $this->export->csv($request);
    }

    public function receipt(Donation $donation)
    {
        $this->authorize('view', $donation);
        $donation->load(['campaign:id,title', 'user:id,name', 'payment:id,donation_id,order_id']);

        return response()->view('admin.donations.receipt', compact('donation'));
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Donation::class);

        return view('admin.donations.index', [
            'donations' => $this->query->paginate($request),
            'summary' => $this->query->summary($request->user()),
        ]);
    }

    public function show(Donation $donation)
    {
        $this->authorize('view', $donation);

        $donation->load([
            'campaign:id,title,slug,organization_id',
            'campaign.organization:id,name,slug',
            'user:id,name,email',
            'payment:id,donation_id,provider,order_id,channel_code,status,expired_at,paid_at,signature,raw_request_json,raw_callback_json',
            'payment.webhooks:id,provider,event,order_id,processed_at,created_at',
        ]);

        return view('admin.donations.show', compact('donation'));
    }
}
