<?php

namespace Modules\Payment\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Payment\App\Queries\Admin\PaymentMonitoringQuery;
use Modules\Payment\App\Services\Admin\PaymentExportService;
use Modules\Payment\App\Services\Admin\PaymentReconciliationService;
use Modules\Payment\Entities\Payment;

class PaymentAdminController extends Controller
{
    public function __construct(
        private readonly PaymentMonitoringQuery $query,
        private readonly PaymentReconciliationService $reconciliation,
        private readonly PaymentExportService $export,
    ) {
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        return $this->export->csv($request);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        return view('admin.payments.index', [
            'payments' => $this->query->paginate($request),
            'summary' => $this->query->summary(),
            'mismatchCount' => $this->reconciliation->count(),
        ]);
    }

    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load([
            'donation:id,campaign_id,user_id,gross_amount,fee_platform,fee_channel,net_amount,method,anonymous,status,paid_at,created_at',
            'donation.campaign:id,title,slug',
            'donation.user:id,name,email',
            'webhooks:id,provider,event,order_id,signature,payload,processed_at,created_at',
        ]);

        return view('admin.payments.show', compact('payment'));
    }

    public function webhooks(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        return view('admin.payments.webhooks', [
            'webhooks' => $this->query->webhookPaginate($request),
        ]);
    }

    public function reconciliation()
    {
        $this->authorize('viewAny', Payment::class);

        return view('admin.payments.reconciliation', [
            'mismatches' => $this->reconciliation->mismatches(),
        ]);
    }
}
