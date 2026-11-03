<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach ([
            'logo_url' => '/icon/icon-web.png',
            'favicon_url' => '/icon/salamaid-favicon.svg',
        ] as $key => $path) {
            DB::table('app_settings')
                ->where('key', $key)
                ->where(fn ($query) => $query->whereNull('value')->orWhere('value', ''))
                ->update(['value' => $path, 'updated_at' => now()]);
        }

        Cache::forget('salamaid.app_settings');
    }

    public function down(): void
    {
        DB::table('app_settings')
            ->whereIn('key', ['logo_url', 'favicon_url'])
            ->whereIn('value', ['/icon/icon-web.png', '/icon/salamaid-favicon.svg'])
            ->update(['value' => '', 'updated_at' => now()]);

        Cache::forget('salamaid.app_settings');
    }
};
