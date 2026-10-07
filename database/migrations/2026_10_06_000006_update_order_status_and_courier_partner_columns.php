<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert enum columns to VARCHAR(50) to allow flexible order and courier delivery statuses
        DB::statement("ALTER TABLE orders MODIFY COLUMN order_status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE orders MODIFY COLUMN courier_partner VARCHAR(50) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN order_status ENUM('pending','confirmed','processing','packed','shipped','out_for_delivery','delivered','cancelled','returned') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE orders MODIFY COLUMN courier_partner ENUM('steadfast','pathao','redx','internal') NULL");
    }
};

