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
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_key', 50)->unique();
            $table->string('name', 100);
            $table->text('store_id');
            $table->text('store_passwd');
            $table->string('base_url', 255)->nullable();
            $table->string('currency', 10)->default('BDT');
            $table->decimal('fee_percentage', 5, 2)->default(2.00);
            $table->boolean('is_active')->default(true);
            $table->boolean('sandbox_mode')->default(true);
            $table->json('additional_settings')->nullable();
            $table->timestamps();

            $table->index(['gateway_key', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};

