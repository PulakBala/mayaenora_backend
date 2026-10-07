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
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'title')) {
                $table->string('title', 150)->nullable()->change();
            }
            if (! Schema::hasColumn('expenses', 'payment_method')) {
                $table->string('payment_method', 50)->default('cash')->after('amount');
            }
            if (! Schema::hasColumn('expenses', 'receipt_image')) {
                $table->string('receipt_image', 255)->nullable()->after('receipt_attachment');
            }
            if (! Schema::hasColumn('expenses', 'description')) {
                $table->text('description')->nullable()->after('note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('expenses', 'receipt_image')) {
                $table->dropColumn('receipt_image');
            }
            if (Schema::hasColumn('expenses', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
