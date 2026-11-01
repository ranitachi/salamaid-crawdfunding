<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_updates', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('campaign_id')->index();
            $t->string('title');
            $t->longText('content');
            $t->string('media_url')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('campaign_updates');
    }
};
