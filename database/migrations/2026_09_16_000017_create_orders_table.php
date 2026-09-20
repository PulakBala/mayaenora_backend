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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 60)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();

            // Customer & Delivery Details
            $table->string('customer_name', 120);
            $table->string('customer_phone', 20);
            $table->string('customer_email', 150)->nullable();
            $table->string('shipping_district', 60);
            $table->text('shipping_address');

            // Item Totals & Customer Billings
            $table->decimal('subtotal_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('delivery_charge', 10, 2);
            $table->decimal('grand_total', 12, 2);

            // Profit & Loss Metrics
            $table->decimal('total_product_cost', 12, 2)->default(0.00);
            $table->decimal('packaging_cost', 10, 2)->default(0.00);
            $table->decimal('actual_courier_cost', 10, 2)->default(0.00);
            $table->decimal('payment_gateway_fee', 10, 2)->default(0.00);
            $table->decimal('net_profit', 12, 2)->default(0.00);

            // Statuses
            $table->enum('payment_method', ['cod', 'bkash', 'nagad', 'sslcommerz', 'card'])->default('cod');
            $table->enum('payment_status', ['pending', 'paid', 'partially_paid', 'refunded', 'failed'])->default('pending');
            $table->enum('order_status', [
                'pending', 'confirmed', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'returned'
            ])->default('pending');

            // Logistics & Tracking
            $table->enum('courier_partner', ['steadfast', 'pathao', 'redx', 'internal'])->nullable();
            $table->string('courier_tracking_code', 100)->nullable();
            $table->string('courier_consignment_id', 100)->nullable();

            $table->string('customer_notes', 255)->nullable();
            $table->string('admin_notes', 255)->nullable();
            $table->string('cancelled_reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('order_status', 'idx_order_status');
            $table->index('customer_phone', 'idx_order_customer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

