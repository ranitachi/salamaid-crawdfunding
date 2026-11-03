<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AppSettingsService
{
    public const CACHE_KEY = 'salamaid.app_settings';

    public const PUBLIC_NAVIGATION_DEFAULTS = [
        ['label' => 'Beranda', 'icon' => 'home', 'route' => 'public.home'],
        ['label' => 'Program', 'icon' => 'grid_view', 'route' => 'public.program'],
        ['label' => 'Lembaga', 'icon' => 'domain', 'route' => 'public.organizations.index'],
        ['label' => 'Donasi', 'icon' => 'favorite', 'route' => 'public.donations'],
        ['label' => 'Inbox', 'icon' => 'mail', 'route' => 'public.inbox'],
        ['label' => 'Akun', 'icon' => 'account_circle', 'route' => 'public.user'],
    ];

    /** @var array<string, string> */
    public const DEFAULTS = [
        'app_name' => 'Salamaid',
        'app_tagline' => 'Admin Console',
        'public_tagline' => 'Jembatan Kebaikan',
        'primary_color' => '#D92D20',
        'primary_soft_color' => '#FEE4E2',
        'accent_color' => '#FF806F',
        'brand_icon' => 'S',
        'logo_url' => '/icon/icon-web.png',
        'favicon_url' => '/icon/salamaid-favicon.svg',
        'contact_email' => '',
        'contact_phone' => '',
        'contact_address' => '',
        'contact_city' => '',
        'contact_country' => 'Indonesia',
        'about_title' => 'Tentang Salamaid',
        'about_content' => 'Salamaid adalah platform gotong royong yang mempertemukan kepedulian dengan program kebaikan yang transparan.',
        'contact_description' => 'Hubungi tim Salamaid melalui kanal berikut untuk pertanyaan, kerja sama, atau bantuan.',
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

    /** @return array<int, array{label: string, icon: string, route: string}> */
    public function publicNavigation(): array
    {
        if (! Schema::hasTable('app_settings')) {
            return self::PUBLIC_NAVIGATION_DEFAULTS;
        }

        $raw = AppSetting::query()->where('key', 'public_navigation')->value('value');
        $items = json_decode((string) $raw, true);

        if (! is_array($items) || count($items) < 3) {
            return self::PUBLIC_NAVIGATION_DEFAULTS;
        }

        return collect($items)->filter(fn ($item): bool => is_array($item)
            && filled($item['label'] ?? null)
            && filled($item['icon'] ?? null)
            && filled($item['route'] ?? null)
            && \Illuminate\Support\Facades\Route::has($item['route']))->map(fn ($item): array => [
                'label' => (string) $item['label'],
                'icon' => (string) $item['icon'],
                'route' => (string) $item['route'],
            ])->values()->all() ?: self::PUBLIC_NAVIGATION_DEFAULTS;
    }
}
