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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 120);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->string('banner_image', 255)->nullable();
            $table->string('meta_title', 160)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

