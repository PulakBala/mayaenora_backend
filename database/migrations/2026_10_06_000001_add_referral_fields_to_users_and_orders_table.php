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
        // 1. Add referral fields to users table
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 32)->nullable()->unique()->after('email');
            }
            if (! Schema::hasColumn('users', 'referred_by_id')) {
                $table->foreignId('referred_by_id')
                    ->nullable()
                    ->after('referral_code')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        // 2. Add referral tracking fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'referrer_id')) {
                $table->foreignId('referrer_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'referral_commission_amount')) {
                $table->decimal('referral_commission_amount', 12, 2)->default(0.00)->after('referrer_id');
            }
            if (! Schema::hasColumn('orders', 'is_referral_commission_awarded')) {
                $table->boolean('is_referral_commission_awarded')->default(false)->after('referral_commission_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'referrer_id')) {
                $table->dropForeign(['referrer_id']);
                $table->dropColumn(['referrer_id', 'referral_commission_amount', 'is_referral_commission_awarded']);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'referred_by_id')) {
                $table->dropForeign(['referred_by_id']);
                $table->dropColumn(['referred_by_id', 'referral_code']);
            }
        });
    }
};

