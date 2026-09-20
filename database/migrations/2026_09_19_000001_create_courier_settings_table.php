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
        Schema::create('courier_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->unique();
            $table->string('name', 100);
            $table->text('api_key');
            $table->text('secret_key');
            $table->text('client_id')->nullable();
            $table->string('base_url', 255)->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('sandbox_mode')->default(true);
            $table->text('webhook_secret')->nullable();
            $table->json('additional_settings')->nullable();
            $table->timestamps();

            $table->index(['provider', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_settings');
    }
};

