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
        Schema::table('banners', function (Blueprint $table) {
            // Make existing restrictive columns nullable if present
            if (Schema::hasColumn('banners', 'desktop_image')) {
                $table->string('desktop_image', 255)->nullable()->change();
            }
            if (Schema::hasColumn('banners', 'position')) {
                $table->string('position', 50)->nullable()->change();
            }
            if (Schema::hasColumn('banners', 'status')) {
                $table->string('status', 20)->nullable()->change();
            }

            // Add hero slider and general banner enhancement columns
            if (! Schema::hasColumn('banners', 'tag')) {
                $table->string('tag', 100)->nullable()->after('title');
            }
            if (! Schema::hasColumn('banners', 'subtitle')) {
                $table->text('subtitle')->nullable()->after('tag');
            }
            if (! Schema::hasColumn('banners', 'image')) {
                $table->string('image', 255)->nullable()->after('subtitle');
            }
            if (! Schema::hasColumn('banners', 'link_url')) {
                $table->string('link_url', 255)->nullable()->after('image');
            }
            if (! Schema::hasColumn('banners', 'button_text')) {
                $table->string('button_text', 100)->nullable()->default('Shop Now')->after('link_url');
            }
            if (! Schema::hasColumn('banners', 'placement')) {
                $table->string('placement', 50)->default('hero_slider')->index()->after('button_text');
            }
            if (! Schema::hasColumn('banners', 'is_active')) {
                $table->boolean('is_active')->default(true)->index()->after('placement');
            }
            if (! Schema::hasColumn('banners', 'start_date')) {
                $table->dateTime('start_date')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('banners', 'end_date')) {
                $table->dateTime('end_date')->nullable()->after('start_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $columnsToDrop = [
                'tag',
                'subtitle',
                'image',
                'link_url',
                'button_text',
                'placement',
                'is_active',
                'start_date',
                'end_date',
            ];

            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('banners', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
