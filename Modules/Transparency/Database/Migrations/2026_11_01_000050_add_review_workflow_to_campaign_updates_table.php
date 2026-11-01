<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A9: review workflow for campaign updates / fund usage reports, plus
     * evidence file metadata. Files themselves live on a private Storage disk.
     */
    public function up(): void
    {
        Schema::table('campaign_updates', function (Blueprint $table) {
            $table->string('type', 30)->default('update')->after('campaign_id')->index();
            $table->string('status', 30)->default('draft')->after('type')->index();
            $table->uuid('payout_id')->nullable()->after('status')->index();
            $table->unsignedBigInteger('amount_used')->nullable()->after('payout_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('amount_used');
            $table->timestamp('submitted_at')->nullable()->after('media_url');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('submitted_at')->index();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
            $table->timestamp('unpublished_at')->nullable()->after('published_at');
        });

        // Existing rows that were already public stay public.
        DB::table('campaign_updates')->whereNotNull('published_at')->update(['status' => 'published']);

        Schema::create('campaign_update_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_update_id')->index();
            $table->string('disk', 50);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->char('checksum', 64);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_update_attachments');

        Schema::table('campaign_updates', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payout_id']);
            $table->dropIndex(['reviewed_by']);
            $table->dropColumn([
                'type', 'status', 'payout_id', 'amount_used', 'created_by',
                'submitted_at', 'reviewed_by', 'reviewed_at', 'review_note', 'unpublished_at',
            ]);
        });
    }
};
