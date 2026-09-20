<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // 2. Permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->string('module', 50);
            $table->timestamps();
        });

        // 3. Role Has Permissions Pivot
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // 4. Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->foreignId('role_id')->default(1)->constrained('roles');
            $table->string('name', 120);
            $table->string('email', 150)->nullable()->unique();
            $table->string('phone', 20)->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('password', 255);
            $table->text('two_factor_secret')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            $table->string('avatar', 255)->nullable();
            $table->rememberToken();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('phone', 'idx_user_phone');
            $table->index('status', 'idx_user_status');
        });

        // 5. Password Reset Tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 6. Sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
