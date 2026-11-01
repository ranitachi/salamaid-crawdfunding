<?php

namespace Modules\Core\App\Services;

use App\Contracts\AdminAuditLogger;
use App\Exceptions\AdminActionException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Enums\OrganizationStatus;
use Modules\Core\App\Events\OrganizationStatusChanged;
use Modules\Core\Entities\Organization;

class OrganizationReviewService
{
    public function __construct(private AdminAuditLogger $audit)
    {
    }

    public function approve(Organization $organization, User $actor): Organization
    {
        return $this->transition(
            $organization,
            OrganizationStatus::VERIFIED,
            $actor,
            null,
            'organization.approve',
            [
                'verified_at' => now(),
                'rejection_reason' => null,
                'suspension_reason' => null,
                'suspended_at' => null,
            ],
        );
    }

    public function reject(Organization $organization, User $actor, string $reason): Organization
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw AdminActionException::requirement('Alasan penolakan organisasi wajib diisi.');
        }

        return $this->transition(
            $organization,
            OrganizationStatus::REJECTED,
            $actor,
            $reason,
            'organization.reject',
            [
                'verified_at' => null,
                'rejection_reason' => $reason,
                'suspension_reason' => null,
                'suspended_at' => null,
            ],
        );
    }

    public function suspend(Organization $organization, User $actor, string $reason): Organization
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw AdminActionException::requirement('Alasan penangguhan organisasi wajib diisi.');
        }

        return $this->transition(
            $organization,
            OrganizationStatus::SUSPENDED,
            $actor,
            $reason,
            'organization.suspend',
            [
                'suspension_reason' => $reason,
                'suspended_at' => now(),
            ],
        );
    }

    /**
     * Restore organisasi suspended kembali ke verified, atau rejected ke pending
     * agar dapat diajukan ulang.
     */
    public function restore(Organization $organization, User $actor): Organization
    {
        $current = OrganizationStatus::tryFrom((string) $organization->status);

        $target = match ($current) {
            OrganizationStatus::SUSPENDED => OrganizationStatus::VERIFIED,
            OrganizationStatus::REJECTED => OrganizationStatus::PENDING,
            default => null,
        };

        if (! $target) {
            throw AdminActionException::invalidTransition(
                'organisasi',
                (string) $organization->status,
                OrganizationStatus::PENDING->value,
            );
        }

        return $this->transition(
            $organization,
            $target,
            $actor,
            null,
            'organization.restore',
            [
                'rejection_reason' => null,
                'suspension_reason' => null,
                'suspended_at' => null,
                'verified_at' => $target === OrganizationStatus::VERIFIED ? now() : null,
            ],
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function transition(
        Organization $organization,
        OrganizationStatus $target,
        User $actor,
        ?string $reason,
        string $action,
        array $attributes = [],
    ): Organization {
        $current = OrganizationStatus::tryFrom((string) $organization->status);

        if (! $current) {
            throw AdminActionException::invalidTransition(
                'organisasi',
                (string) $organization->status,
                $target->value,
            );
        }

        if (! $current->canTransitionTo($target)) {
            throw AdminActionException::invalidTransition('organisasi', $current->value, $target->value);
        }

        $before = $organization->only([
            'status',
            'verified_at',
            'rejection_reason',
            'suspension_reason',
            'suspended_at',
        ]);

        $from = $current->value;

        $updated = DB::transaction(function () use ($organization, $target, $actor, $attributes, &$from) {
            /** @var Organization $locked */
            $locked = Organization::query()
                ->whereKey($organization->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedStatus = OrganizationStatus::tryFrom((string) $locked->status);

            if (! $lockedStatus || ! $lockedStatus->canTransitionTo($target)) {
                throw AdminActionException::invalidTransition(
                    'organisasi',
                    (string) $locked->status,
                    $target->value,
                );
            }

            $from = $lockedStatus->value;

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
            ['module' => 'Core', 'entity' => 'organization'],
        );

        event(new OrganizationStatusChanged($updated, $from, $target->value, $actor, $reason));

        return $updated->refresh();
    }
}
