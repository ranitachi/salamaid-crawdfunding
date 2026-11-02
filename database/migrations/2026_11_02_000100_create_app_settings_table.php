<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('branding')->index();
            $table->string('type', 20)->default('string');
            $table->timestamps();
        });

        $now = now();
        $defaults = [
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

        DB::table('app_settings')->insert(array_map(
            fn (string $key, string $value): array => [
                'key' => $key,
                'value' => $value,
                'group' => 'branding',
                'type' => 'string',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_keys($defaults),
            array_values($defaults),
        ));
        Cache::forever('salamaid.app_settings', $defaults);
    }

    public function down(): void
    {
        Cache::forget('salamaid.app_settings');
        Schema::dropIfExists('app_settings');
    }
};
