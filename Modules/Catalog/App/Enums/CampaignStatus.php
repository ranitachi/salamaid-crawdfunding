<?php

namespace Modules\Catalog\App\Enums;

enum CampaignStatus: string
{
    case DRAFT = 'draft';
    case UNDER_REVIEW = 'under_review';
    case PUBLISHED = 'published';
    case CLOSED = 'closed';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::UNDER_REVIEW => 'Menunggu Review',
            self::PUBLISHED => 'Dipublikasikan',
            self::CLOSED => 'Ditutup',
            self::REJECTED => 'Ditolak',
            self::SUSPENDED => 'Ditangguhkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::DRAFT => 'muted',
            self::UNDER_REVIEW => 'warning',
            self::PUBLISHED => 'success',
            self::CLOSED => 'info',
            self::REJECTED => 'danger',
            self::SUSPENDED => 'muted',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next->value, self::transitions()[$this->value] ?? [], true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function transitions(): array
    {
        return [
            self::DRAFT->value => [self::UNDER_REVIEW->value],
            self::UNDER_REVIEW->value => [self::PUBLISHED->value, self::REJECTED->value],
            self::PUBLISHED->value => [self::CLOSED->value, self::SUSPENDED->value],
            self::SUSPENDED->value => [self::PUBLISHED->value],
            self::REJECTED->value => [self::DRAFT->value],
            self::CLOSED->value => [],
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
