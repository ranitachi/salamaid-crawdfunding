<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // campaigns
        Schema::create('campaigns', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('organization_id')->index();
            $t->uuid('category_id')->nullable()->index();
            $t->string('title');
            $t->string('slug')->unique();
            $t->longText('description');
            $t->unsignedBigInteger('target_amount')->default(0);
            $t->unsignedBigInteger('collected_amount')->default(0);
            $t->timestamp('deadline_at')->nullable();
            $t->string('location_label')->nullable();
            $t->string('cover_url')->nullable();
            $t->enum('status',['draft','under_review','published','closed','rejected','suspended'])->default('draft');
            $t->boolean('is_featured')->default(false);
            $t->json('meta_json')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['status','deadline_at']);
        });

        // campaign_media
        Schema::create('campaign_media', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('campaign_id')->index();
            $t->enum('type',['image','video','doc'])->default('image');
            $t->string('url');
            $t->string('caption')->nullable();
            $t->integer('sort')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_media');
        Schema::dropIfExists('campaigns');
    }
};
