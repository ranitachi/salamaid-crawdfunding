<?php

namespace Modules\Payment\App\Services\Admin;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\Payment\App\Queries\Admin\PaymentMonitoringQuery;

class PaymentExportService
{
    public function __construct(private readonly PaymentMonitoringQuery $query)
    {
    }

    public function csv(Request $request): StreamedResponse
    {
        $rows = $this->query->forExport($request)->lazy();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Tanggal', 'Provider', 'Order ID', 'Channel', 'Status', 'Donation ID', 'Amount']);
            foreach ($rows as $payment) {
                fputcsv($out, [$payment->id, optional($payment->created_at)->toDateTimeString(), $payment->provider, $payment->order_id, $payment->channel_code, $payment->status, $payment->donation_id, $payment->donation?->gross_amount]);
            }
            fclose($out);
        }, 'payments-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
