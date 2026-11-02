<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A7: provider status sync history and webhook processing attempts.
     * Additive only; the original payment migration is untouched.
     */
    public function up(): void
    {
        Schema::create('payment_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_id')->index();
            $table->string('provider', 50);
            $table->string('trigger', 20);          // manual | webhook | retry
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->string('outcome', 20)->index(); // updated | unchanged | unmapped | mismatch | failed | unsupported
            $table->string('provider_status', 50)->nullable();
            $table->string('status_before', 20)->nullable();
            $table->string('status_after', 20)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('error', 255)->nullable();
            $table->json('response_summary')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::table('webhooks', function (Blueprint $table) {
            $table->unsignedSmallInteger('attempts')->default(0)->after('payload');
            $table->string('last_error', 255)->nullable()->after('attempts');
            $table->timestamp('failed_at')->nullable()->after('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'last_error', 'failed_at']);
        });

        Schema::dropIfExists('payment_sync_logs');
    }
};
