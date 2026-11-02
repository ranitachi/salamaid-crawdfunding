<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => 'public_tagline'],
            [
                'value' => 'Jembatan Kebaikan',
                'group' => 'branding',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('app_settings')->where('key', 'public_tagline')->delete();
    }
};
