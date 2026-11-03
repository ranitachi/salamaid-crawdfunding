<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach ([
            'about_title' => 'Tentang Salamaid',
            'about_content' => 'Salamaid adalah platform gotong royong yang mempertemukan kepedulian dengan program kebaikan yang transparan.',
            'contact_description' => 'Hubungi tim Salamaid melalui kanal berikut untuk pertanyaan, kerja sama, atau bantuan.',
        ] as $key => $value) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'group' => 'content', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            );
        }

        Cache::forget('salamaid.app_settings');
    }

    public function down(): void
    {
        DB::table('app_settings')->whereIn('key', ['about_title', 'about_content', 'contact_description'])->delete();
        Cache::forget('salamaid.app_settings');
    }
};
