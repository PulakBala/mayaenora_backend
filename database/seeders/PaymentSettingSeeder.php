<?php

namespace Database\Seeders;

use App\Models\PaymentSetting;
use Illuminate\Database\Seeder;

class PaymentSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentSetting::updateOrCreate(
            ['gateway_key' => 'sslcommerz'],
            [
                'name' => 'SSLCommerz Payment Gateway (Cards / bKash / Nagad)',
                'store_id' => 'testbox',
                'store_passwd' => 'qwerty',
                'base_url' => 'https://sandbox.sslcommerz.com',
                'currency' => 'BDT',
                'fee_percentage' => 2.00,
                'is_active' => true,
                'sandbox_mode' => true,
                'additional_settings' => [
                    'product_profile' => 'general',
                    'product_category' => 'Apparel & Fashion',
                    'allowed_card_types' => ['visa', 'master', 'amex', 'bkash', 'nagad', 'rocket', 'dbblnexus'],
                ],
            ]
        );
    }
}

