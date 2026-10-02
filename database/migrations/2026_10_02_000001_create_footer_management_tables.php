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
        if (! Schema::hasTable('footer_settings')) {
            Schema::create('footer_settings', function (Blueprint $table) {
                $table->id();
                $table->text('brand_bio')->nullable();
                $table->string('copyright_text')->nullable();
                $table->string('bottom_slogan')->nullable();
                $table->string('newsletter_title')->nullable();
                $table->string('newsletter_subtitle')->nullable();
                $table->boolean('newsletter_enabled')->default(true);
                $table->json('social_links')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone')->nullable();
                $table->text('contact_address')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('footer_sections')) {
            Schema::create('footer_sections', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('footer_links')) {
            Schema::create('footer_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('footer_section_id')->constrained('footer_sections')->cascadeOnDelete();
                $table->string('title');
                $table->string('url');
                $table->boolean('open_in_new_tab')->default(false);
                $table->integer('sort_order')->default(0);
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
        Schema::dropIfExists('footer_links');
        Schema::dropIfExists('footer_sections');
        Schema::dropIfExists('footer_settings');
    }
};
