<?php

namespace Modules\Transparency\App\Enums;

enum CampaignUpdateType: string
{
    /** Narrative progress update. */
    case UPDATE = 'update';

    /** Fund usage report; must carry evidence files and the amount used. */
    case FUND_REPORT = 'fund_report';

    public function label(): string
    {
        return match ($this) {
            self::UPDATE => 'Update campaign',
            self::FUND_REPORT => 'Laporan penggunaan dana',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
