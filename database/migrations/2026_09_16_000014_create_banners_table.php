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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150)->nullable();
            $table->string('target_url', 255)->nullable();
            $table->string('desktop_image', 255);
            $table->string('mobile_image', 255)->nullable();
            $table->enum('position', ['top_slider', 'home_middle', 'sidebar', 'popup'])->default('top_slider');
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};

