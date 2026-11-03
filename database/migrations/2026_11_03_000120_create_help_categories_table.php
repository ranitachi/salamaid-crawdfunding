<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 80)->unique();
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        foreach ([
            ['name' => 'Akun dan profil', 'slug' => 'akun-dan-profil', 'sort_order' => 10],
            ['name' => 'Donasi dan pembayaran', 'slug' => 'donasi-dan-pembayaran', 'sort_order' => 20],
            ['name' => 'Campaign dan transparansi', 'slug' => 'campaign-dan-transparansi', 'sort_order' => 30],
            ['name' => 'Keamanan dan privasi', 'slug' => 'keamanan-dan-privasi', 'sort_order' => 40],
            ['name' => 'Umum', 'slug' => 'umum', 'sort_order' => 99],
        ] as $category) {
            DB::table('help_categories')->insert($category + ['id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
        }

        Schema::table('help_articles', function (Blueprint $table): void {
            $table->uuid('help_category_id')->nullable()->after('id')->index();
            $table->foreign('help_category_id')->references('id')->on('help_categories')->nullOnDelete();
        });

        foreach (DB::table('help_categories')->get(['id', 'name']) as $category) {
            DB::table('help_articles')->where('category', $category->name)->update(['help_category_id' => $category->id]);
        }
    }

    public function down(): void
    {
        Schema::table('help_articles', function (Blueprint $table): void {
            $table->dropForeign(['help_category_id']);
            $table->dropColumn('help_category_id');
        });
        Schema::dropIfExists('help_categories');
    }
};
