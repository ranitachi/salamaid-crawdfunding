<?php

namespace Modules\Donation\App\Services\Admin;

use App\Support\CsvSanitizer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\Donation\App\Queries\Admin\DonationMonitoringQuery;

class DonationExportService
{
    public function __construct(private readonly DonationMonitoringQuery $query)
    {
    }

    public function csv(Request $request): StreamedResponse
    {
        $rows = $this->query->forExport($request, $request->user())->lazy();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Tanggal', 'Campaign', 'Donatur', 'Status', 'Metode', 'Gross', 'Net', 'Order ID']);
            foreach ($rows as $donation) {
                fputcsv($out, CsvSanitizer::row([$donation->id, optional($donation->created_at)->toDateTimeString(), $donation->campaign?->title, $donation->anonymous ? 'Anonim' : ($donation->user?->name ?? 'Guest'), $donation->status, $donation->method, $donation->gross_amount, $donation->net_amount, $donation->payment?->order_id]));
            }
            fclose($out);
        }, 'donations-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
