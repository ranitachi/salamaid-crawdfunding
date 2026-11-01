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
            DB::statement('ALTER TABLE `payouts` MODIFY `requested_by` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `payouts` MODIFY `approved_by` BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE payouts ALTER COLUMN requested_by TYPE BIGINT USING requested_by::bigint');
            DB::statement('ALTER TABLE payouts ALTER COLUMN approved_by TYPE BIGINT USING approved_by::bigint');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `payouts` MODIFY `requested_by` CHAR(36) NOT NULL');
            DB::statement('ALTER TABLE `payouts` MODIFY `approved_by` CHAR(36) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE payouts ALTER COLUMN requested_by TYPE UUID USING requested_by::uuid');
            DB::statement('ALTER TABLE payouts ALTER COLUMN approved_by TYPE UUID USING approved_by::uuid');
        }
    }
};
