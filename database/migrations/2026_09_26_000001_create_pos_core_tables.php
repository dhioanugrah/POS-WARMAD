<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->enum('type', ['store', 'warehouse'])->default('store');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('doc_type', 10);
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('period', 8);
            $table->unsignedInteger('last_number')->default(0);
            $table->unique(['doc_type', 'location_id', 'period']);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 50)->unique()->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'default_location_id')) {
                $table->foreignId('default_location_id')->nullable()->constrained('locations')->restrictOnDelete()->after('username');
            }
            if (! Schema::hasColumn('users', 'supervisor_pin')) {
                $table->string('supervisor_pin')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('supervisor_pin');
            }
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('is_active');
            }
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('phone', 30)->nullable()->index();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('cash_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('type', ['in', 'out']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_categories');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_location_id']);
            $table->dropColumn(['username', 'default_location_id', 'supervisor_pin', 'is_active', 'last_login_at']);
        });

        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('settings');
    }
};
