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
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('commission_value', 10, 2)->default(5.00); // 5% or ৳50
            $table->decimal('minimum_order_value', 12, 2)->default(500.00); // e.g. ৳500
            $table->decimal('minimum_payout_threshold', 12, 2)->default(500.00); // e.g. ৳500
            $table->enum('reward_frequency', ['first_order_only', 'all_orders'])->default('first_order_only');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_settings');
    }
};

