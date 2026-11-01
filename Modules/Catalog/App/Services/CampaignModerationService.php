<?php

namespace Modules\Catalog\App\Services;

use App\Contracts\AdminAuditLogger;
use App\Exceptions\AdminActionException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\App\Enums\CampaignStatus;
use Modules\Catalog\App\Events\CampaignStatusChanged;
use Modules\Catalog\Entities\Campaign;

class CampaignModerationService
{
    public function __construct(private AdminAuditLogger $audit)
    {
    }

    public function submitForReview(Campaign $campaign, User $actor): Campaign
    {
        return $this->transition(
            $campaign,
            CampaignStatus::UNDER_REVIEW,
            $actor,
            null,
            'campaign.submit',
            [
                'rejection_reason' => null,
                'suspension_reason' => null,
            ],
        );
    }

    public function publish(Campaign $campaign, User $actor): Campaign
    {
        return $this->transition(
            $campaign,
            CampaignStatus::PUBLISHED,
            $actor,
            null,
            'campaign.publish',
            [
                'published_at' => $campaign->published_at ?? now(),
                'rejection_reason' => null,
                'suspension_reason' => null,
            ],
            fn (Campaign $locked) => $this->assertPublishable($locked),
        );
    }

    public function reject(Campaign $campaign, User $actor, string $reason): Campaign
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw AdminActionException::requirement('Alasan penolakan campaign wajib diisi.');
        }

        return $this->transition(
            $campaign,
            CampaignStatus::REJECTED,
            $actor,
            $reason,
            'campaign.reject',
            [
                'rejection_reason' => $reason,
                'suspension_reason' => null,
            ],
        );
    }

    public function suspend(Campaign $campaign, User $actor, string $reason): Campaign
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw AdminActionException::requirement('Alasan penangguhan campaign wajib diisi.');
        }

        return $this->transition(
            $campaign,
            CampaignStatus::SUSPENDED,
            $actor,
            $reason,
            'campaign.suspend',
            [
                'suspension_reason' => $reason,
            ],
        );
    }

    /**
     * Restore campaign suspended ke published, atau rejected ke draft.
     */
    public function restore(Campaign $campaign, User $actor): Campaign
    {
        $current = CampaignStatus::tryFrom((string) $campaign->status);

        if ($current === CampaignStatus::SUSPENDED) {
            return $this->transition(
                $campaign,
                CampaignStatus::PUBLISHED,
                $actor,
                null,
                'campaign.restore',
                [
                    'suspension_reason' => null,
                    'published_at' => $campaign->published_at ?? now(),
                ],
                fn (Campaign $locked) => $this->assertPublishable($locked),
            );
        }

        if ($current === CampaignStatus::REJECTED) {
            return $this->transition(
                $campaign,
                CampaignStatus::DRAFT,
                $actor,
                null,
                'campaign.restore',
                [
                    'rejection_reason' => null,
                ],
            );
        }

        throw AdminActionException::invalidTransition(
            'campaign',
            (string) $campaign->status,
            CampaignStatus::PUBLISHED->value,
        );
    }

    public function close(Campaign $campaign, User $actor): Campaign
    {
        return $this->transition(
            $campaign,
            CampaignStatus::CLOSED,
            $actor,
            null,
            'campaign.close',
            [
                'closed_at' => now(),
            ],
        );
    }

    public function feature(Campaign $campaign, User $actor): Campaign
    {
        if ($campaign->is_featured) {
            return $campaign;
        }

        return $this->updateFeaturedFlag($campaign, true, $actor, 'campaign.feature');
    }

    public function unfeature(Campaign $campaign, User $actor): Campaign
    {
        if (! $campaign->is_featured) {
            return $campaign;
        }

        return $this->updateFeaturedFlag($campaign, false, $actor, 'campaign.unfeature');
    }

    /**
     * Soft delete. Campaign published tidak boleh dihapus dari admin.
     */
    public function delete(Campaign $campaign, User $actor): void
    {
        if ($campaign->isPublished()) {
            throw AdminActionException::requirement('Campaign yang sudah published tidak dapat dihapus.');
        }

        $before = $campaign->only(['status', 'is_featured', 'deleted_at']);

        $this->audit->log(
            'campaign.delete',
            $campaign,
            $before,
            ['deleted_at' => now()],
            null,
            $actor,
            ['module' => 'Catalog', 'entity' => 'campaign', 'type' => 'soft_delete'],
        );

        $campaign->delete();
    }

    /**
     * Hapus permanen hanya untuk campaign yang belum published dan belum
     * memiliki donasi. Ini menjaga integritas data finansial.
     */
    public function forceDelete(Campaign $campaign, User $actor): void
    {
        if ($campaign->isPublished()) {
            throw AdminActionException::requirement('Campaign yang sudah published tidak dapat dihapus permanen.');
        }

        if ($campaign->hasDonations() || (int) $campaign->collected_amount > 0) {
            throw AdminActionException::requirement('Campaign yang memiliki donasi tidak dapat dihapus permanen.');
        }

        $before = $campaign->only(['status', 'is_featured', 'collected_amount']);

        $this->audit->log(
            'campaign.force_delete',
            $campaign,
            $before,
            [],
            null,
            $actor,
            ['module' => 'Catalog', 'entity' => 'campaign', 'type' => 'force_delete'],
        );

        $campaign->forceDelete();
    }

    /**
     * @param array<string, mixed> $attributes
     * @param callable(Campaign): void|null $beforeUpdate
     */
    private function transition(
        Campaign $campaign,
        CampaignStatus $target,
        User $actor,
        ?string $reason,
        string $action,
        array $attributes = [],
        ?callable $beforeUpdate = null,
    ): Campaign {
        $initial = CampaignStatus::tryFrom((string) $campaign->status);

        if (! $initial || ! $initial->canTransitionTo($target)) {
            throw AdminActionException::invalidTransition(
                'campaign',
                (string) $campaign->status,
                $target->value,
            );
        }

        $before = $campaign->only([
            'status',
            'rejection_reason',
            'suspension_reason',
            'reviewed_by',
            'reviewed_at',
            'published_at',
            'closed_at',
            'is_featured',
            'featured_at',
        ]);

        $from = $initial->value;

        $updated = DB::transaction(function () use ($campaign, $target, $actor, $attributes, $beforeUpdate, &$from) {
            /** @var Campaign $locked */
            $locked = Campaign::query()
                ->whereKey($campaign->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $current = CampaignStatus::tryFrom((string) $locked->status);

            if (! $current || ! $current->canTransitionTo($target)) {
                throw AdminActionException::invalidTransition(
                    'campaign',
                    (string) $locked->status,
                    $target->value,
                );
            }

            $from = $current->value;

            if ($beforeUpdate) {
                $beforeUpdate($locked);
            }

            $locked->forceFill(array_merge($attributes, [
                'status' => $target->value,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
            ]))->save();

            return $locked;
        });

        $after = $updated->only(array_keys($before));

        $this->audit->log(
            $action,
            $updated,
            $before,
            $after,
            $reason,
            $actor,
            ['module' => 'Catalog', 'entity' => 'campaign'],
        );

        event(new CampaignStatusChanged($updated, $from, $target->value, $actor, $reason));

        return $updated->refresh();
    }

    private function updateFeaturedFlag(
        Campaign $campaign,
        bool $featured,
        User $actor,
        string $action,
    ): Campaign {
        if (! $campaign->isPublished() && ! $campaign->isSuspended()) {
            throw AdminActionException::requirement('Hanya campaign published yang dapat di-feature.');
        }

        $before = $campaign->only(['is_featured', 'featured_at']);

        $updated = DB::transaction(function () use ($campaign, $featured) {
            /** @var Campaign $locked */
            $locked = Campaign::query()
                ->whereKey($campaign->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $locked->forceFill([
                'is_featured' => $featured,
                'featured_at' => $featured ? now() : null,
            ])->save();

            return $locked;
        });

        $this->audit->log(
            $action,
            $updated,
            $before,
            $updated->only(['is_featured', 'featured_at']),
            null,
            $actor,
            ['module' => 'Catalog', 'entity' => 'campaign', 'type' => 'featured_flag'],
        );

        return $updated->refresh();
    }

    private function assertPublishable(Campaign $campaign): void
    {
        $organization = $campaign->organization;

        if (! $organization || ! $organization->isVerified()) {
            throw AdminActionException::requirement(
                'Campaign tidak dapat dipublikasikan karena organisasi belum terverifikasi.',
            );
        }

        if ((int) $campaign->target_amount <= 0) {
            throw AdminActionException::requirement('Target dana campaign harus lebih dari nol.');
        }

        if (! $campaign->deadline_at || $campaign->deadline_at->isPast()) {
            throw AdminActionException::requirement('Deadline campaign harus diisi dan belum lewat.');
        }

        if (trim((string) $campaign->description) === '') {
            throw AdminActionException::requirement('Deskripsi campaign wajib diisi sebelum publikasi.');
        }

        if (! $campaign->media()->exists() && trim((string) $campaign->cover_url) === '') {
            throw AdminActionException::requirement(
                'Campaign membutuhkan minimal satu media atau cover sebelum publikasi.',
            );
        }
    }
}
