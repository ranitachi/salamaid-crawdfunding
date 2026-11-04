<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => 'favicon_url'],
            [
                'value' => '/icon/salamaid-favicon.svg',
                'group' => 'branding',
                'type' => 'string',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        Cache::forget('salamaid.app_settings');
    }

    public function down(): void
    {
        // Keep the configured favicon on rollback; it is a branding setting.
    }
};
