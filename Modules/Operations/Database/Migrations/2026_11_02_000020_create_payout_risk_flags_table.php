<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payout_risk_flags', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('payout_id')->index();
            $table->uuid('organization_id')->index();
            $table->string('code', 80);
            $table->enum('severity', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['open', 'reviewed', 'dismissed', 'confirmed'])->default('open');
            $table->string('reason', 500);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['payout_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_risk_flags');
    }
};
