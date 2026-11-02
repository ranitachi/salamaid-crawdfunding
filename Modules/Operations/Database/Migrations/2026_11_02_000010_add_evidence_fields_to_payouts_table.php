<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('evidence_path')->nullable()->after('evidence_url');
            $table->string('evidence_mime')->nullable()->after('evidence_path');
            $table->integer('evidence_size')->nullable()->after('evidence_mime');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['evidence_path', 'evidence_mime', 'evidence_size']);
        });
    }
};