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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('variant_sku', 100)->unique();
            $table->string('size', 50)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('material', 50)->nullable();
            $table->string('variant_name', 150)->nullable();
            $table->decimal('cost_price', 12, 2)->default(0.00);
            $table->decimal('regular_price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->string('image', 255)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

