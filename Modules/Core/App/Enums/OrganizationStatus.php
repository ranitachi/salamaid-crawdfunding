<?php

namespace Modules\Core\App\Enums;

enum OrganizationStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Verifikasi',
            self::VERIFIED => 'Terverifikasi',
            self::REJECTED => 'Ditolak',
            self::SUSPENDED => 'Ditangguhkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::VERIFIED => 'success',
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
            self::PENDING->value => [self::VERIFIED->value, self::REJECTED->value],
            self::VERIFIED->value => [self::SUSPENDED->value],
            self::SUSPENDED->value => [self::VERIFIED->value],
            self::REJECTED->value => [self::PENDING->value],
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
