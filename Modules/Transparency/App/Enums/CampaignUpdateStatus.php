<?php

namespace Modules\Transparency\App\Enums;

enum CampaignUpdateStatus: string
{
    case DRAFT = 'draft';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PUBLISHED = 'published';
    case UNPUBLISHED = 'unpublished';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::UNDER_REVIEW => 'Menunggu Review',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Perlu Revisi',
            self::PUBLISHED => 'Dipublikasikan',
            self::UNPUBLISHED => 'Ditarik',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, self::transitions()[$this->value], true);
    }

    /** Evidence can only change while the organization is still preparing the update. */
    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::REJECTED], true);
    }

    /**
     * draft/rejected → under_review → approved|rejected
     * approved|unpublished → published → unpublished
     *
     * @return array<string, array<int, self>>
     */
    public static function transitions(): array
    {
        return [
            self::DRAFT->value => [self::UNDER_REVIEW],
            self::REJECTED->value => [self::UNDER_REVIEW],
            self::UNDER_REVIEW->value => [self::APPROVED, self::REJECTED],
            self::APPROVED->value => [self::PUBLISHED],
            self::PUBLISHED->value => [self::UNPUBLISHED],
            self::UNPUBLISHED->value => [self::PUBLISHED],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
