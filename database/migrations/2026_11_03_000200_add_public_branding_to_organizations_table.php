<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('tagline', 160)->nullable()->after('description');
            $table->string('banner_url')->nullable()->after('logo_url');
            $table->string('primary_color', 7)->nullable()->after('banner_url');
            $table->string('secondary_color', 7)->nullable()->after('primary_color');
            $table->string('website_url')->nullable()->after('contact_phone');
            $table->string('instagram_url')->nullable()->after('website_url');
            $table->string('facebook_url')->nullable()->after('instagram_url');
            $table->string('whatsapp_url')->nullable()->after('facebook_url');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'tagline', 'banner_url', 'primary_color', 'secondary_color',
                'website_url', 'instagram_url', 'facebook_url', 'whatsapp_url',
            ]);
        });
    }
};
