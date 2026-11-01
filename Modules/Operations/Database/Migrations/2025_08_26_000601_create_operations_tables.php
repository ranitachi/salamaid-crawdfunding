<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('organization_id')->index();
            $t->unsignedBigInteger('amount');
            $t->enum('status',['requested','approved','rejected','paid'])->default('requested');
            $t->unsignedBigInteger('requested_by')->index();
            $t->unsignedBigInteger('approved_by')->nullable()->index();
            $t->timestamp('paid_at')->nullable();
            $t->string('evidence_url')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
