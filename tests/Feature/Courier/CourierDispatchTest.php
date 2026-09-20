<?php

namespace Tests\Feature\Courier;

use App\Models\CourierSetting;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\Courier\CourierManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CourierDispatchTest extends TestCase
{
    use DatabaseTransactions;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->order = Order::create([
            'order_number' => 'ORD-DISPATCH-' . strtoupper(uniqid()),
            'customer_name' => 'Recipient Name',
            'customer_phone' => '01711000000',
            'customer_email' => 'recipient@example.com',
            'shipping_district' => 'Dhaka',
            'shipping_address' => 'House 1, Road 2, Gulshan 1',
            'subtotal_amount' => 1500.00,
            'delivery_charge' => 60.00,
            'grand_total' => 1560.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'confirmed',
        ]);
    }

    public function test_courier_settings_table_stores_and_encrypts_credentials(): void
    {
        $rawKey = 'secret_super_secure_key_12345';
        $setting = CourierSetting::updateOrCreate(
            ['provider' => 'steadfast'],
            [
                'name' => 'Steadfast Courier',
                'api_key' => $rawKey,
                'secret_key' => 'secret_val_67890',
                'base_url' => 'https://portal.steadfast.com.bd/api/v1',
                'is_active' => true,
                'sandbox_mode' => true,
            ]
        );

        $this->assertEquals($rawKey, $setting->api_key);

        // Verify the raw database value is encrypted (not plain text)
        $rawDbRow = \Illuminate\Support\Facades\DB::table('courier_settings')
            ->where('provider', 'steadfast')
            ->first();

        $this->assertNotEquals($rawKey, $rawDbRow->api_key);
        $this->assertNotEmpty($rawDbRow->api_key);
    }

    public function test_courier_manager_can_dispatch_order_and_updates_status_and_history(): void
    {
        // Ensure steadfast is configured and active in sandbox mode
        CourierSetting::updateOrCreate(
            ['provider' => 'steadfast'],
            [
                'name' => 'Steadfast Courier',
                'api_key' => 'sandbox_key',
                'secret_key' => 'sandbox_secret',
                'base_url' => 'https://portal.steadfast.com.bd/api/v1',
                'is_active' => true,
                'sandbox_mode' => true,
            ]
        );

        $result = CourierManager::dispatchOrder($this->order, 'steadfast', [
            'notes' => 'Urgent delivery requested',
        ]);

        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->trackingCode);
        $this->assertNotEmpty($result->consignmentId);
        $this->assertStringContainsString('https://steadfast.com.bd/t/', $result->trackingUrl);

        // Verify order was updated in DB
        $this->order->refresh();
        $this->assertEquals('steadfast', $this->order->courier_partner);
        $this->assertEquals($result->trackingCode, $this->order->courier_tracking_code);
        $this->assertEquals($result->consignmentId, $this->order->courier_consignment_id);
        $this->assertEquals('shipped', $this->order->order_status);

        // Verify history log was created
        $history = OrderStatusHistory::where('order_id', $this->order->id)
            ->where('status', 'shipped')
            ->first();

        $this->assertNotNull($history);
        $this->assertStringContainsString('Steadfast', $history->comment);
        $this->assertStringContainsString($result->trackingCode, $history->comment);
    }

    public function test_courier_webhook_updates_order_status_and_logs_history(): void
    {
        // First set order as dispatched with tracking code
        $this->order->update([
            'courier_partner' => 'steadfast',
            'courier_tracking_code' => 'ST_WEBHOOK_TEST_123',
            'courier_consignment_id' => 'SF_WEBHOOK_TEST_123',
            'order_status' => 'shipped',
        ]);

        $payload = [
            'status' => 'delivered',
            'tracking_code' => 'ST_WEBHOOK_TEST_123',
            'consignment_id' => 'SF_WEBHOOK_TEST_123',
            'invoice' => $this->order->order_number,
        ];

        $response = $this->postJson('/api/v1/webhooks/courier/steadfast', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->order->refresh();
        $this->assertEquals('delivered', $this->order->order_status);
        $this->assertEquals('paid', $this->order->payment_status);

        $history = OrderStatusHistory::where('order_id', $this->order->id)
            ->where('status', 'delivered')
            ->first();

        $this->assertNotNull($history);
        $this->assertStringContainsString('Steadfast', $history->comment);
    }
}

