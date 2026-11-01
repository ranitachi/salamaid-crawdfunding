<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `organizations` MODIFY `reviewed_by` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `campaigns` MODIFY `reviewed_by` BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE organizations ALTER COLUMN reviewed_by TYPE BIGINT USING reviewed_by::bigint');
            DB::statement('ALTER TABLE campaigns ALTER COLUMN reviewed_by TYPE BIGINT USING reviewed_by::bigint');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `organizations` MODIFY `reviewed_by` CHAR(36) NULL');
            DB::statement('ALTER TABLE `campaigns` MODIFY `reviewed_by` CHAR(36) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE organizations ALTER COLUMN reviewed_by TYPE UUID USING reviewed_by::uuid');
            DB::statement('ALTER TABLE campaigns ALTER COLUMN reviewed_by TYPE UUID USING reviewed_by::uuid');
        }
    }
};
