<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Payment\SSLCommerzService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SSLCommerzIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected Order $order;
    protected PaymentSetting $setting;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure active SSLCommerz setting exists
        $this->setting = PaymentSetting::updateOrCreate(
            ['gateway_key' => 'sslcommerz'],
            [
                'name' => 'SSLCommerz Test Gateway',
                'store_id' => 'testbox',
                'store_passwd' => 'qwerty',
                'base_url' => 'https://sandbox.sslcommerz.com',
                'currency' => 'BDT',
                'fee_percentage' => 2.50,
                'is_active' => true,
                'sandbox_mode' => true,
            ]
        );

        // Create a test order
        $this->order = Order::create([
            'order_number' => 'ORD-TEST-' . strtoupper(uniqid()),
            'customer_name' => 'Kazi Nazrul',
            'customer_phone' => '01712345678',
            'customer_email' => 'nazrul@example.com',
            'shipping_district' => 'Dhaka',
            'shipping_address' => 'House 42, Road 11, Banani',
            'subtotal_amount' => 2500.00,
            'delivery_charge' => 60.00,
            'grand_total' => 2560.00,
            'payment_method' => 'sslcommerz',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);
    }

    public function test_payment_settings_encrypts_credentials_at_rest(): void
    {
        $rawStorePasswd = 'my_super_secret_merchant_password_9988';

        $setting = PaymentSetting::updateOrCreate(
            ['gateway_key' => 'sslcommerz_test_encryption'],
            [
                'name' => 'Encryption Test Gateway',
                'store_id' => 'store123',
                'store_passwd' => $rawStorePasswd,
                'currency' => 'BDT',
                'is_active' => true,
                'sandbox_mode' => true,
            ]
        );

        // Model attribute should be decrypted on retrieval
        $this->assertEquals($rawStorePasswd, $setting->store_passwd);

        // Raw database column value should NOT equal plain text
        $rawDbRow = DB::table('payment_settings')
            ->where('gateway_key', 'sslcommerz_test_encryption')
            ->first();

        $this->assertNotEquals($rawStorePasswd, $rawDbRow->store_passwd);
    }

    public function test_sslcommerz_service_initiates_payment_session(): void
    {
        /** @var SSLCommerzService $service */
        $service = app(SSLCommerzService::class);
        $result = $service->initiatePayment($this->order);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['redirect_url']);
    }

    public function test_sslcommerz_success_callback_marks_order_paid_and_creates_transaction(): void
    {
        $mockValId = 'mock_val_' . uniqid();
        $mockBankTran = 'BNK-' . strtoupper(uniqid());

        $payload = [
            'tran_id' => $this->order->order_number,
            'val_id' => $mockValId,
            'amount' => '2560.00',
            'card_type' => 'VISA-CityBank',
            'bank_tran_id' => $mockBankTran,
            'status' => 'VALID',
        ];

        $response = $this->post(route('api.payment.sslcommerz.success'), $payload);

        // Should redirect to order success page
        $response->assertRedirect();
        $this->assertStringContainsString('order-success', $response->headers->get('Location'));
        $this->assertStringContainsString($this->order->order_number, $response->headers->get('Location'));

        // Refresh Order from database
        $this->order->refresh();
        $this->assertEquals('paid', $this->order->payment_status);
        $this->assertEquals('confirmed', $this->order->order_status);
        $this->assertEquals('sslcommerz', $this->order->payment_method);
        $this->assertGreaterThan(0, $this->order->payment_gateway_fee);

        // Verify Payment transaction record in payments table
        $paymentRecord = Payment::where('order_id', $this->order->id)
            ->where('transaction_id', $mockBankTran)
            ->first();

        $this->assertNotNull($paymentRecord);
        $this->assertEquals('success', $paymentRecord->status);
        $this->assertEquals(2560.00, (float) $paymentRecord->amount);
        $this->assertStringContainsString('VISA-CityBank', $paymentRecord->gateway_name);
    }

    public function test_sslcommerz_fail_callback_updates_order_status(): void
    {
        $payload = [
            'tran_id' => $this->order->order_number,
            'error' => 'Insufficient funds in customer card.',
            'status' => 'FAILED',
        ];

        $response = $this->post(route('api.payment.sslcommerz.fail'), $payload);

        $response->assertRedirect();
        $this->assertStringContainsString('order-failed', $response->headers->get('Location'));
        $this->assertStringContainsString('status=failed', $response->headers->get('Location'));

        $this->order->refresh();
        $this->assertEquals('failed', $this->order->payment_status);

        // Verify failed payment logged
        $payment = Payment::where('order_id', $this->order->id)
            ->where('status', 'failed')
            ->first();
        $this->assertNotNull($payment);
    }

    public function test_sslcommerz_cancel_callback_updates_order_status(): void
    {
        $payload = [
            'tran_id' => $this->order->order_number,
            'status' => 'CANCELLED',
        ];

        $response = $this->post(route('api.payment.sslcommerz.cancel'), $payload);

        $response->assertRedirect();
        $this->assertStringContainsString('checkout', $response->headers->get('Location'));
        $this->assertStringContainsString('status=cancelled', $response->headers->get('Location'));
        $this->assertStringContainsString($this->order->order_number, $response->headers->get('Location'));

        $this->order->refresh();
        $this->assertEquals('cancelled', $this->order->payment_status);
    }

    public function test_sslcommerz_ipn_handles_webhook_notification(): void
    {
        $mockValId = 'mock_ipn_' . uniqid();
        $mockBankTran = 'IPN-' . strtoupper(uniqid());

        $payload = [
            'tran_id' => $this->order->order_number,
            'val_id' => $mockValId,
            'amount' => '2560.00',
            'card_type' => 'bKash-MSISDN',
            'bank_tran_id' => $mockBankTran,
            'status' => 'VALID',
        ];

        $response = $this->postJson(route('api.payment.sslcommerz.ipn'), $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->order->refresh();
        $this->assertEquals('paid', $this->order->payment_status);
    }

    public function test_checkout_order_endpoint_returns_redirect_url_for_sslcommerz(): void
    {
        // Find or create product variant
        $variant = ProductVariant::first();
        if (! $variant) {
            $product = Product::create([
                'title' => 'Test Silk Scarf',
                'slug' => 'test-silk-scarf-' . uniqid(),
                'status' => 'published',
            ]);
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'variant_sku' => 'SKU-' . uniqid(),
                'sale_price' => 1200.00,
                'cost_price' => 700.00,
                'stock' => 50,
            ]);
        }

        $payload = [
            'customer_name' => 'Habib Rahman',
            'customer_phone' => '01812345678',
            'customer_email' => 'habib@example.com',
            'shipping_district' => 'Dhaka',
            'shipping_address' => 'Mirpur DOHS, Road 9',
            'delivery_method' => 'inside-dhaka',
            'payment_method' => 'sslcommerz',
            'items' => [
                [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/checkout/order', $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'redirect_url',
            'payment_type',
            'data' => [
                'order_number',
                'pricing' => ['grand_total'],
                'status' => ['payment_status'],
            ],
        ]);

        $this->assertNotEmpty($response->json('redirect_url'));
        $this->assertEquals('online', $response->json('payment_type'));
    }

    public function test_checkout_order_show_endpoint_retrieves_receipt(): void
    {
        $response = $this->getJson("/api/v1/checkout/order/{$this->order->order_number}");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.order_number', $this->order->order_number);
        $this->assertEquals(2560.00, (float) $response->json('data.pricing.grand_total'));
    }

    public function test_checkout_rejects_disallowed_payment_methods(): void
    {
        $variant = ProductVariant::first();

        // Testing bkash should fail validation
        $response = $this->postJson('/api/v1/checkout/order', [
            'customer_name' => 'Habib Rahman',
            'customer_phone' => '01812345678',
            'shipping_district' => 'Dhaka',
            'shipping_address' => 'Mirpur DOHS, Road 9',
            'delivery_method' => 'inside-dhaka',
            'payment_method' => 'bkash',
            'items' => [
                [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_method']);

        // Testing card should fail validation
        $responseCard = $this->postJson('/api/v1/checkout/order', [
            'customer_name' => 'Habib Rahman',
            'customer_phone' => '01812345678',
            'shipping_district' => 'Dhaka',
            'shipping_address' => 'Mirpur DOHS, Road 9',
            'delivery_method' => 'inside-dhaka',
            'payment_method' => 'card',
            'items' => [
                [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $responseCard->assertStatus(422);
        $responseCard->assertJsonValidationErrors(['payment_method']);
    }
}


