<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\AdminAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\Admin\ReportQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportQuery $query,
        private readonly AdminAuditLogger $audit,
    )
    {
    }

    public function index(Request $request)
    {
        return view('admin.reports.index', ['report' => $this->query->summary($request)]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'admin-report-'.now()->format('Ymd-His').'.csv';
        $rows = $this->query->exportRows($request);
        /** @var User $actor */
        $actor = $request->user();

        $this->audit->log(
            'report.export',
            $actor,
            [],
            ['metric_rows' => max(0, count($rows) - 1)],
            null,
            $actor,
            [
                'module' => 'Reports',
                'filters' => array_filter($request->only(['date_from', 'date_to']), static fn ($value) => is_string($value)),
            ],
        );

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'wb');
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
