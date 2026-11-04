<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('app_settings')
            ->where('key', 'favicon_url')
            ->whereIn('value', ['', '/icon/salamaid-favicon.svg'])
            ->update(['value' => '/icon/icon-web.png', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('app_settings')
            ->where('key', 'favicon_url')
            ->where('value', '/icon/icon-web.png')
            ->update(['value' => '/icon/salamaid-favicon.svg', 'updated_at' => now()]);
    }
};
