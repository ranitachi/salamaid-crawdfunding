<?php

namespace App\Queries\Admin;

use App\Models\AdminAuditLog;
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

        $pendingPayoutDays = max(1, (int) config('dashboard.payout_pending_days', 3));
        $oldPendingPayouts = Payout::query()
            ->whereIn('status', ['requested', 'approved'])
            ->where('created_at', '<', now()->subDays($pendingPayoutDays))
            ->count();

        $alerts = array_values(array_filter([
            $this->alert(
                'payment',
                'Payment gagal/expired',
                'Payment gagal atau expired perlu diperiksa.',
                (int) Payment::query()->whereIn('status', ['failed', 'expired'])->count(),
                '/admin/payments-reconciliation',
            ),
            $this->alert(
                'webhook',
                'Webhook belum diproses',
                'Webhook provider menunggu diproses.',
                (int) Webhook::query()->whereNull('processed_at')->count(),
                '/admin/payments-reconciliation',
            ),
            $this->alert(
                'mismatch',
                'Payment mismatch',
                'Status donation dan payment tidak konsisten.',
                (int) $this->reconciliation->count(),
                '/admin/payments-reconciliation',
            ),
            $this->alert(
                'payout',
                'Payout tertunda',
                "Payout terbuka lebih dari {$pendingPayoutDays} hari.",
                $oldPendingPayouts,
                '/admin/payouts?status=requested',
            ),
        ]));

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
            'alerts' => $alerts,
        ];
    }

    private function alert(string $key, string $title, string $description, int $count, string $url): ?array
    {
        return $count > 0 ? compact('key', 'title', 'description', 'count', 'url') : null;
    }

    private function activities(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $auditActivities = $this->auditActivities($start, $end);

        if ($auditActivities !== []) {
            return $auditActivities;
        }

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

    private function auditActivities(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return AdminAuditLog::query()
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->limit(5)
            ->get(['action', 'subject_type', 'subject_id', 'actor_email', 'created_at'])
            ->map(function (AdminAuditLog $log): array {
                $url = match (true) {
                    str_contains($log->subject_type, 'CampaignUpdate') => route('admin.transparency.show', $log->subject_id),
                    str_contains($log->subject_type, 'Campaign') => route('admin.campaigns.show', $log->subject_id),
                    str_contains($log->subject_type, 'Organization') => route('admin.organizations.show', $log->subject_id),
                    str_contains($log->subject_type, 'Payout') => route('admin.payouts.show', $log->subject_id),
                    default => route('admin.audit-logs.index', ['q' => $log->action]),
                };

                return [
                    'at' => $log->created_at,
                    'title' => str_replace('.', ' · ', $log->action),
                    'description' => ($log->actor_email ?: 'System').' · '.$log->subject_id,
                    'url' => $url,
                ];
            })->all();
    }
}
