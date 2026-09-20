<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::create([
            'code' => 'ELVOA10',
            'type' => 'percent',
            'discount_value' => 10.00,
            'min_order_amount' => 500.00,
            'max_discount_amount' => 500.00,
            'usage_limit' => 500,
            'used_count' => 14,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addMonths(3),
            'status' => 'active',
        ]);

        Coupon::create([
            'code' => 'WELCOME50',
            'type' => 'fixed',
            'discount_value' => 50.00,
            'min_order_amount' => 300.00,
            'max_discount_amount' => 50.00,
            'usage_limit' => 1000,
            'used_count' => 32,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addMonths(6),
            'status' => 'active',
        ]);
    }
}

