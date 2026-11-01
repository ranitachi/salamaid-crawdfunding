<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom tambahan untuk moderasi campaign dan penyesuaian nilai status.
     *
     * Status campaign pada migration A0 masih berupa enum tanpa "suspended".
     * Migration ini menambahkan nilai tersebut tanpa mengubah data yang ada.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('meta_json');
            $table->text('suspension_reason')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('suspension_reason')->index();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('published_at')->nullable()->after('reviewed_at');
            $table->timestamp('closed_at')->nullable()->after('published_at');
            $table->timestamp('featured_at')->nullable()->after('closed_at');
            $table->index('is_featured');
        });

        $this->allowSuspendedStatus();
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('campaigns')->where('status', 'suspended')->update(['status' => 'published']);
            DB::statement("ALTER TABLE `campaigns` MODIFY `status` ENUM('draft','under_review','published','closed','rejected') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_reviewed_by_index');
            $table->dropIndex('campaigns_is_featured_index');
            $table->dropColumn([
                'rejection_reason',
                'suspension_reason',
                'reviewed_by',
                'reviewed_at',
                'published_at',
                'closed_at',
                'featured_at',
            ]);
        });
    }

    private function allowSuspendedStatus(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `campaigns` MODIFY `status` ENUM('draft','under_review','published','closed','rejected','suspended') NOT NULL DEFAULT 'draft'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE campaigns DROP CONSTRAINT IF EXISTS campaigns_status_check');
            DB::statement('ALTER TABLE campaigns ALTER COLUMN status TYPE VARCHAR(32)');
            DB::statement("ALTER TABLE campaigns ALTER COLUMN status SET DEFAULT 'draft'");

            return;
        }

        if ($driver === 'sqlite' && ! $this->sqliteStatusAllowsSuspended()) {
            $this->rebuildSqliteCampaignsTable();
        }
    }

    private function sqliteStatusAllowsSuspended(): bool
    {
        $sql = DB::table('sqlite_master')
            ->where('type', 'table')
            ->where('name', 'campaigns')
            ->value('sql');

        return is_string($sql) && str_contains($sql, "'suspended'");
    }

    /**
     * SQLite tidak mendukung ALTER pada CHECK constraint. Tabel dibangun ulang
     * hanya untuk database SQLite lama yang belum memiliki status suspended.
     * Database fresh memakai migration awal yang sudah memuat nilai tersebut.
     */
    private function rebuildSqliteCampaignsTable(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('campaigns_rebuild', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('category_id')->nullable()->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->unsignedBigInteger('target_amount')->default(0);
            $table->unsignedBigInteger('collected_amount')->default(0);
            $table->timestamp('deadline_at')->nullable();
            $table->string('location_label')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('status', 32)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->json('meta_json')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->text('rejection_reason')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('featured_at')->nullable();
        });

        $columns = [
            'id', 'organization_id', 'category_id', 'title', 'slug', 'description',
            'target_amount', 'collected_amount', 'deadline_at', 'location_label',
            'cover_url', 'status', 'is_featured', 'meta_json', 'created_at', 'updated_at',
            'deleted_at', 'rejection_reason', 'suspension_reason', 'reviewed_by',
            'reviewed_at', 'published_at', 'closed_at', 'featured_at',
        ];

        $columnList = implode(', ', array_map(fn (string $column) => '"'.$column.'"', $columns));

        DB::statement("INSERT INTO campaigns_rebuild ({$columnList}) SELECT {$columnList} FROM campaigns");
        Schema::drop('campaigns');
        Schema::rename('campaigns_rebuild', 'campaigns');

        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['status', 'deadline_at']);
            $table->index('is_featured');
            $table->index('reviewed_by');
        });

        Schema::enableForeignKeyConstraints();
    }
};
