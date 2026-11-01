<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // payments
        Schema::create('payments', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('donation_id')->unique();
            $t->string('provider')->default('faspay');
            $t->string('order_id')->unique();          // bill_no
            $t->string('channel_code');                // mapping Faspay
            $t->string('va_number')->nullable();
            $t->text('qris_payload')->nullable();
            $t->string('redirect_url')->nullable();
            $t->enum('status',['pending','paid','expired','failed'])->default('pending');
            $t->timestamp('expired_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->string('signature')->nullable();
            $t->json('raw_request_json')->nullable();
            $t->json('raw_callback_json')->nullable();
            $t->timestamps();
        });

        // payment_channels (referensi)
        Schema::create('payment_channels', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('provider')->default('faspay');
            $t->string('code')->unique();    // kode Faspay
            $t->string('method');            // qris|va_bri|ovo|...
            $t->string('label');             // "QRIS", "VA BRI"
            $t->boolean('active')->default(true);
            $t->json('meta_json')->nullable();
            $t->timestamps();
        });

        // payment_fees (tarif channel)
        Schema::create('payment_fees', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('payment_channel_id')->index();
            $t->unsignedBigInteger('fee_flat')->default(0);
            $t->decimal('fee_percent',5,2)->default(0);
            $t->unsignedBigInteger('min_fee')->default(0);
            $t->unsignedBigInteger('max_fee')->nullable();
            $t->date('effective_from');
            $t->date('effective_to')->nullable();
            $t->timestamps();
        });

        // webhooks log
        Schema::create('webhooks', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('provider');
            $t->string('event')->nullable();
            $t->string('order_id')->index();
            $t->string('signature')->nullable();
            $t->json('payload');
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
        });

        // refunds (opsional disiapkan)
        Schema::create('refunds', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('donation_id')->index();
            $t->unsignedBigInteger('amount');
            $t->string('reason')->nullable();
            $t->enum('status',['requested','processing','done','rejected'])->default('requested');
            $t->string('provider_ref')->nullable();
            $t->uuid('created_by');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('payment_fees');
        Schema::dropIfExists('payment_channels');
        Schema::dropIfExists('payments');
    }
};
