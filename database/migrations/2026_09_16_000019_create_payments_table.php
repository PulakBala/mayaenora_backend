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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('transaction_id', 120)->unique();
            $table->string('gateway_name', 50); // bKash, Nagad, SSLCommerz, etc.
            $table->decimal('amount', 12, 2);
            $table->decimal('fee_deducted', 8, 2)->default(0.00);
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

