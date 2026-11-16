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
        if (! Schema::hasTable('theme_settings')) {
            Schema::create('theme_settings', function (Blueprint $table) {
                $table->id();
                $table->string('header_bg_color')->default('#ffffff');
                $table->string('header_text_color')->default('#111827');
                $table->string('footer_bg_color')->default('#111827');
                $table->string('footer_text_color')->default('#d1d5db');
                $table->string('button_bg_color')->default('#FF5B37');
                $table->string('button_hover_color')->default('#eb4e2a');
                $table->string('button_text_color')->default('#ffffff');
                $table->string('body_bg_color')->default('#ffffff');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
