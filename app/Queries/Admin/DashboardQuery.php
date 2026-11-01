<?php

namespace App\Queries\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Modules\Operations\Entities\Payout;
use Modules\Payment\App\Services\Admin\PaymentReconciliationService;
use Modules\Payment\Entities\Payment;
use Modules\Payment\Entities\Webhook;

class DashboardQuery
{
    public function __construct(private readonly PaymentReconciliationService $reconciliation)
    {
    }

    /** @param array{period?: string, date_from?: string, date_to?: string} $filters */
    public function data(array $filters = []): array
    {
        [$start, $end, $periodLabel] = $this->period($filters);
        $cacheSeconds = max(0, (int) config('dashboard.cache_seconds', 0));
        $cacheKey = 'admin.dashboard.'.sha1($start->toIso8601String().'|'.$end->toIso8601String());
        $resolver = fn () => $this->buildData($start, $end, $periodLabel);

        return $cacheSeconds > 0
            ? Cache::remember($cacheKey, now()->addSeconds($cacheSeconds), $resolver)
            : $resolver();
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string} */
    private function period(array $filters): array
    {
        $timezone = (string) config('dashboard.timezone', config('app.timezone', 'UTC'));
        $now = CarbonImmutable::now($timezone);
        $period = $filters['period'] ?? 'month';

        return match ($period) {
            'today' => [$now->startOfDay(), $now->endOfDay(), 'Hari ini'],
            '7d' => [$now->subDays(6)->startOfDay(), $now->endOfDay(), '7 hari terakhir'],
            '30d' => [$now->subDays(29)->startOfDay(), $now->endOfDay(), '30 hari terakhir'],
            'custom' => [
                CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['date_from'], $timezone)->startOfDay(),
                CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['date_to'], $timezone)->endOfDay(),
                'Periode kustom',
            ],
            default => [$now->startOfMonth(), $now->endOfDay(), 'Bulan ini'],
        };
    }

    private function buildData(CarbonImmutable $start, CarbonImmutable $end, string $periodLabel): array
    {
        $campaigns = Campaign::query()
            ->with('organization:id,name')
            ->where('status', 'under_review')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return [
            'period' => [
                'label' => $periodLabel,
                'start' => $start,
                'end' => $end,
                'timezone' => (string) config('dashboard.timezone', config('app.timezone', 'UTC')),
            ],
            'summary' => [
                'active_campaigns' => Campaign::query()->where('status', 'published')->count(),
                'campaigns_review' => Campaign::query()->where('status', 'under_review')->count(),
                'pending_organizations' => Organization::query()->where('status', 'pending')->count(),
                'monthly_donations' => (int) Donation::query()->where('status', 'paid')->whereBetween('created_at', [$start, $end])->sum('gross_amount'),
                'pending_payouts' => Payout::query()->where('status', 'requested')->count(),
                'pending_payout_amount' => (int) Payout::query()->whereIn('status', ['requested', 'approved'])->sum('amount'),
                'failed_payments' => Payment::query()->whereIn('status', ['failed', 'expired'])->whereBetween('created_at', [$start, $end])->count(),
                'unprocessed_webhooks' => Webhook::query()->whereNull('processed_at')->whereBetween('created_at', [$start, $end])->count(),
                'mismatches' => $this->reconciliation->count(),
            ],
            'campaigns' => $campaigns,
            'activities' => $this->activities($start, $end),
        ];
    }

    private function activities(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $items = collect();

        Campaign::query()->whereNotNull('published_at')->whereBetween('published_at', [$start, $end])->latest('published_at')->limit(3)->get(['id', 'title', 'published_at'])->each(function (Campaign $campaign) use ($items) {
            $items->push(['at' => $campaign->published_at, 'title' => 'Campaign dipublikasikan', 'description' => $campaign->title, 'url' => route('admin.campaigns.show', $campaign)]);
        });

        Organization::query()->whereNotNull('reviewed_at')->whereBetween('reviewed_at', [$start, $end])->latest('reviewed_at')->limit(3)->get(['id', 'name', 'status', 'reviewed_at'])->each(function (Organization $organization) use ($items) {
            $items->push(['at' => $organization->reviewed_at, 'title' => 'Organisasi direview', 'description' => $organization->name.' · '.$organization->status, 'url' => route('admin.organizations.show', $organization)]);
        });

        Payout::query()->whereBetween('created_at', [$start, $end])->latest('created_at')->limit(3)->get(['id', 'amount', 'status', 'created_at'])->each(function (Payout $payout) use ($items) {
            $items->push(['at' => $payout->created_at, 'title' => 'Payout '.$payout->status, 'description' => 'Rp '.number_format($payout->amount, 0, ',', '.'), 'url' => route('admin.payouts.show', $payout)]);
        });

        return $items->sortByDesc('at')->take(5)->values()->all();
    }
}
