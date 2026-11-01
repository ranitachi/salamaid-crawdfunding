<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('campaign_id')->index();
            $t->uuid('user_id')->nullable()->index();
            $t->unsignedBigInteger('gross_amount');
            $t->unsignedBigInteger('fee_platform')->default(0);
            $t->unsignedBigInteger('fee_channel')->default(0);
            $t->unsignedBigInteger('net_amount')->default(0);
            $t->string('method');                       // qris|va_bri|ovo|...
            $t->boolean('anonymous')->default(false);
            $t->string('message', 280)->nullable();
            $t->string('ref_code', 32)->nullable();     // fundraiser
            $t->enum('status', ['pending','paid','expired','refund','failed'])->default('pending');
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('expired_at')->nullable();
            $t->timestamps();

            $t->index(['campaign_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
