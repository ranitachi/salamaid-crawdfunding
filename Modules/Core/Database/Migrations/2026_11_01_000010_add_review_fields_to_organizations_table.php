<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->after('bank_account_json')->index();
            $table->text('rejection_reason')->nullable()->after('status');
            $table->text('suspension_reason')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('suspension_reason')->index();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('suspended_at')->nullable()->after('reviewed_at');
        });

        // Data A0 hanya menyimpan verified_at. Backfill agar organisasi lama
        // yang sudah diverifikasi tidak tampil sebagai pending.
        DB::table('organizations')
            ->whereNotNull('verified_at')
            ->update(['status' => 'verified']);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex('organizations_status_index');
            $table->dropIndex('organizations_reviewed_by_index');
            $table->dropColumn([
                'status',
                'rejection_reason',
                'suspension_reason',
                'reviewed_by',
                'reviewed_at',
                'suspended_at',
            ]);
        });
    }
};
