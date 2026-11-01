<?php

namespace Modules\Transparency\App\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Modules\Transparency\App\Enums\CampaignUpdateStatus;
use Modules\Transparency\Entities\CampaignUpdate;

class CampaignUpdateQuery
{
    /**
     * @param array<string, mixed> $filters validated CampaignUpdateIndexRequest data
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return CampaignUpdate::query()
            ->select(['id', 'campaign_id', 'type', 'status', 'title', 'amount_used', 'submitted_at', 'published_at', 'created_at'])
            ->with(['campaign:id,title,organization_id', 'campaign.organization:id,name'])
            ->withCount('attachments')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['campaign_id'] ?? null, fn ($q, $id) => $q->where('campaign_id', $id))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(fn ($inner) => $inner
                    ->where('title', 'like', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhereHas('campaign', fn ($c) => $c->where('title', 'like', '%'.addcslashes($search, '%_\\').'%')));
            })
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->where('created_at', '<=', Carbon::parse($date)->endOfDay()))
            ->orderByRaw('case when status = ? then 0 else 1 end', [CampaignUpdateStatus::UNDER_REVIEW->value])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = CampaignUpdate::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(CampaignUpdateStatus::cases())
            ->mapWithKeys(fn (CampaignUpdateStatus $s) => [$s->value => (int) ($counts[$s->value] ?? 0)])
            ->all();
    }

    public function detail(CampaignUpdate $update): CampaignUpdate
    {
        return $update->load([
            'campaign:id,title,status,organization_id',
            'campaign.organization:id,name',
            'payout:id,organization_id,amount,status,paid_at',
            'attachments' => fn ($q) => $q->latest(),
            'attachments.uploader:id,name',
            'creator:id,name',
            'reviewer:id,name',
        ]);
    }
}
