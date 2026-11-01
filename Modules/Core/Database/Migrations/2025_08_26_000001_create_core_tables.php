<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // organizations
        Schema::create('organizations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('logo_url')->nullable();
            $t->longText('description')->nullable();
            $t->text('address')->nullable();
            $t->string('contact_email')->nullable();
            $t->string('contact_phone')->nullable();
            $t->json('bank_account_json')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        // organization_members (multi admin per lembaga)
        Schema::create('organization_members', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('organization_id')->index();
            $t->uuid('user_id')->index();
            $t->enum('role', ['owner','admin','finance','editor'])->default('admin');
            $t->timestamps();

            $t->unique(['organization_id','user_id']);
        });

        // categories
        Schema::create('categories', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('icon')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('organization_members');
        Schema::dropIfExists('organizations');
    }
};
