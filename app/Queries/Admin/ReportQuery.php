<?php

namespace App\Queries\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportQuery
{
    public function summary(Request $request): array
    {
        [$from, $to] = $this->dateRange($request);

        return [
            'period' => compact('from', 'to'),
            'campaigns' => $this->campaignSummary($from, $to),
            'donations' => $this->donationSummary($from, $to),
            'payments' => $this->paymentSummary($from, $to),
            'payouts' => $this->payoutSummary($from, $to),
            'fees' => $this->feeSummary($from, $to),
        ];
    }

    private function dateRange(Request $request): array
    {
        $from = $request->date('date_from')?->startOfDay() ?? now()->subDays(30)->startOfDay();
        $to = $request->date('date_to')?->endOfDay() ?? now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function campaignSummary($from, $to): array
    {
        $query = DB::table('campaigns')->whereBetween('created_at', [$from, $to]);

        return [
            'total' => (clone $query)->count(),
            'by_status' => (clone $query)->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->orderBy('status')->pluck('total', 'status')->all(),
        ];
    }

    private function donationSummary($from, $to): array
    {
        $query = DB::table('donations')->whereBetween('created_at', [$from, $to]);

        return [
            'total' => (clone $query)->count(),
            'gross_amount' => (float) (clone $query)->sum('gross_amount'),
            'by_status' => (clone $query)->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->orderBy('status')->pluck('total', 'status')->all(),
        ];
    }

    private function paymentSummary($from, $to): array
    {
        $query = DB::table('payments')->whereBetween('created_at', [$from, $to]);

        return [
            'total' => (clone $query)->count(),
            'by_status' => (clone $query)->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->orderBy('status')->pluck('total', 'status')->all(),
            'by_provider' => (clone $query)->select('provider', DB::raw('count(*) as total'))
                ->groupBy('provider')->orderBy('provider')->pluck('total', 'provider')->all(),
        ];
    }

    private function payoutSummary($from, $to): array
    {
        $query = DB::table('payouts')->whereBetween('created_at', [$from, $to]);

        return [
            'total' => (clone $query)->count(),
            'amount' => (float) (clone $query)->sum('amount'),
            'by_status' => (clone $query)->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->orderBy('status')->pluck('total', 'status')->all(),
        ];
    }

    private function feeSummary($from, $to): array
    {
        $donations = DB::table('donations')->whereBetween('donations.created_at', [$from, $to]);
        $platform = (float) (clone $donations)->sum('fee_platform');
        $channel = (float) (clone $donations)->sum('fee_channel');

        $byChannel = DB::table('donations')
            ->leftJoin('payments', 'payments.donation_id', '=', 'donations.id')
            ->whereBetween('donations.created_at', [$from, $to])
            ->selectRaw("COALESCE(payments.channel_code, donations.method) as channel, SUM(donations.fee_channel) as total")
            ->groupBy('payments.channel_code', 'donations.method')
            ->orderBy('channel')
            ->pluck('total', 'channel')
            ->map(fn ($total) => (float) $total)
            ->all();

        return [
            'platform_amount' => $platform,
            'channel_amount' => $channel,
            'total_amount' => $platform + $channel,
            'by_channel' => $byChannel,
        ];
    }

    public function exportRows(Request $request): array
    {
        $summary = $this->summary($request);
        $rows = [['section', 'metric', 'value', 'status_or_provider']];

        foreach (['campaigns' => ['total'], 'donations' => ['total', 'gross_amount'], 'payments' => ['total'], 'payouts' => ['total', 'amount'], 'fees' => ['platform_amount', 'channel_amount', 'total_amount']] as $section => $metrics) {
            foreach ($metrics as $metric) {
                $rows[] = [$section, $metric, $summary[$section][$metric], ''];
            }
            foreach ($summary[$section]['by_status'] ?? [] as $status => $total) {
                $rows[] = [$section, 'status_count', $total, $status];
            }
            foreach ($summary[$section]['by_provider'] ?? [] as $provider => $total) {
                $rows[] = [$section, 'provider_count', $total, $provider];
            }
            foreach ($summary[$section]['by_channel'] ?? [] as $channel => $total) {
                $rows[] = [$section, 'channel_fee', $total, $channel];
            }
        }

        return $rows;
    }
}
