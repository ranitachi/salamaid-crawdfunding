<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AppSettingsService
{
    public const CACHE_KEY = 'salamaid.app_settings';

    /** @var array<string, string> */
    public const DEFAULTS = [
        'app_name' => 'Salamaid',
        'app_tagline' => 'Admin Console',
        'primary_color' => '#D92D20',
        'primary_soft_color' => '#FEE4E2',
        'accent_color' => '#FF806F',
        'brand_icon' => 'S',
        'logo_url' => '',
        'favicon_url' => '',
        'contact_email' => '',
        'contact_phone' => '',
        'contact_address' => '',
        'contact_city' => '',
        'contact_country' => 'Indonesia',
    ];

    /** @return array<string, string> */
    public function all(): array
    {
        $defaults = self::DEFAULTS;

        if (! Schema::hasTable('app_settings')) {
            return $defaults;
        }

        $stored = Cache::rememberForever(self::CACHE_KEY, fn (): array =>
            AppSetting::query()->pluck('value', 'key')->map(fn ($value): string => (string) $value)->all()
        );

        $settings = array_replace($defaults, $stored);

        foreach (['primary_color', 'primary_soft_color', 'accent_color'] as $color) {
            if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $settings[$color])) {
                $settings[$color] = $defaults[$color];
            }
        }

        return $settings;
    }

    /** @param array<string, string|null> $values */
    public function update(array $values): array
    {
        foreach ($values as $key => $value) {
            AppSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'branding', 'type' => 'string'],
            );
        }

        Cache::forget(self::CACHE_KEY);

        return $this->all();
    }
}
