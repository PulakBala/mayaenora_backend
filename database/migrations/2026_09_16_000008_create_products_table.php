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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('base_sku', 100)->unique();
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            $table->decimal('base_cost_price', 12, 2)->default(0.00);
            $table->decimal('base_selling_price', 12, 2)->default(0.00);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->enum('discount_type', ['fixed', 'percent'])->nullable();
            $table->timestamp('discount_start')->nullable();
            $table->timestamp('discount_end')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->boolean('allow_reviews')->default(true);
            $table->string('meta_title', 160)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->string('meta_keywords', 255)->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();

            $table->index('category_id', 'idx_product_category');
            $table->index(['base_selling_price', 'discount_price'], 'idx_product_prices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

