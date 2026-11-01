<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fundraisers', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('campaign_id')->index();
            $t->uuid('user_id')->index();
            $t->string('ref_code', 32)->unique();
            $t->string('title')->nullable();
            $t->unsignedBigInteger('total_collected')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('fundraisers');
    }
};
