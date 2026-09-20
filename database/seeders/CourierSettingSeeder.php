<?php

namespace Database\Seeders;

use App\Models\CourierSetting;
use Illuminate\Database\Seeder;

class CourierSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $couriers = [
            [
                'provider' => 'steadfast',
                'name' => 'Steadfast Courier',
                'api_key' => 'steadfast_sandbox_api_key',
                'secret_key' => 'steadfast_sandbox_secret_key',
                'client_id' => null,
                'base_url' => 'https://portal.steadfast.com.bd/api/v1',
                'is_active' => true,
                'sandbox_mode' => true,
                'webhook_secret' => 'whsec_steadfast_sandbox',
                'additional_settings' => [
                    'default_weight' => 0.5,
                ],
            ],
            [
                'provider' => 'pathao',
                'name' => 'Pathao Courier',
                'api_key' => 'pathao_sandbox_client_secret',
                'secret_key' => 'pathao_sandbox_password',
                'client_id' => 'pathao_sandbox_client_id',
                'base_url' => 'https://courier-api-hermes.pathao.com',
                'is_active' => true,
                'sandbox_mode' => true,
                'webhook_secret' => 'whsec_pathao_sandbox',
                'additional_settings' => [
                    'store_id' => 1,
                    'delivery_type' => 48,
                    'item_type' => 2,
                ],
            ],
            [
                'provider' => 'redx',
                'name' => 'RedX Logistics',
                'api_key' => 'redx_sandbox_access_token',
                'secret_key' => 'redx_sandbox_secret',
                'client_id' => null,
                'base_url' => 'https://openapi.redx.com.bd/v1.0.0-sandbox',
                'is_active' => true,
                'sandbox_mode' => true,
                'webhook_secret' => 'whsec_redx_sandbox',
                'additional_settings' => [
                    'pickup_store_id' => 1,
                ],
            ],
        ];

        foreach ($couriers as $courier) {
            CourierSetting::updateOrCreate(
                ['provider' => $courier['provider']],
                $courier
            );
        }
    }
}

