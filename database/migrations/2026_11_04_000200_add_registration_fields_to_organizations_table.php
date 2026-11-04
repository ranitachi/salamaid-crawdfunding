<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('organization_type', 40)->nullable()->after('name')->index();
            $table->string('responsible_name', 120)->nullable()->after('organization_type');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('application_reference', 24)->nullable()->unique()->after('status');
            $table->string('verification_document_path')->nullable()->after('application_reference');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropUnique(['application_reference']);
            $table->dropIndex(['organization_type']);
            $table->dropColumn([
                'organization_type',
                'responsible_name',
                'city',
                'province',
                'application_reference',
                'verification_document_path',
            ]);
        });
    }
};
