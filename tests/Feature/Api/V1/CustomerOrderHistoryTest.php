<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerOrderHistoryTest extends TestCase
{
    use DatabaseTransactions;

    protected User $customer;
    protected User $otherCustomer;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer']);

        $this->customer = User::create([
            'role_id' => $role->id,
            'name' => 'Test Customer',
            'phone' => '01700998877',
            'email' => 'customer_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $this->otherCustomer = User::create([
            'role_id' => $role->id,
            'name' => 'Other Customer',
            'phone' => '01800998877',
            'email' => 'other_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $category = Category::firstOrCreate(
            ['slug' => 'test-order-cat'],
            ['name' => 'Test Category', 'is_active' => true]
        );

        $this->product = Product::create([
            'category_id' => $category->id,
            'title' => 'Premium Cotton Polo',
            'slug' => 'test-polo-' . uniqid(),
            'base_sku' => 'SKU-' . uniqid(),
            'base_cost_price' => 500.00,
            'base_selling_price' => 1200.00,
            'is_published' => true,
        ]);

        ProductImage::create([
            'product_id' => $this->product->id,
            'image_url' => 'products/polo.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'variant_sku' => 'VAR-' . uniqid(),
            'color' => 'Navy Blue',
            'size' => 'L',
            'cost_price' => 500.00,
            'regular_price' => 1200.00,
            'sale_price' => 1200.00,
            'stock_quantity' => 20,
            'status' => 'active',
        ]);
    }

    public function test_unauthenticated_request_to_customer_orders_returns_401(): void
    {
        $response = $this->getJson('/api/v1/customer/orders');

        $response->assertStatus(401);
    }

    public function test_authenticated_customer_can_fetch_own_order_history(): void
    {
        // Create an order for customer
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'user_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_phone' => $this->customer->phone,
            'customer_email' => $this->customer->email,
            'shipping_district' => 'Dhaka',
            'shipping_address' => 'House 12, Road 5, Dhanmondi',
            'subtotal_amount' => 1200.00,
            'delivery_charge' => 60.00,
            'grand_total' => 1260.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'shipped',
            'courier_partner' => 'steadfast',
            'courier_tracking_code' => 'ST12345678',
            'courier_consignment_id' => 'SF-998877',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'product_name' => $this->product->title,
            'variant_details' => 'Navy Blue / L',
            'quantity' => 1,
            'unit_cost_price' => 500.00,
            'unit_sale_price' => 1200.00,
            'subtotal' => 1200.00,
            'item_net_profit' => 700.00,
        ]);

        // Create an order for otherCustomer (must NOT appear)
        Order::create([
            'order_number' => 'ORD-OTHER-' . strtoupper(uniqid()),
            'user_id' => $this->otherCustomer->id,
            'customer_name' => $this->otherCustomer->name,
            'customer_phone' => $this->otherCustomer->phone,
            'shipping_district' => 'Chittagong',
            'shipping_address' => 'GEC Circle',
            'subtotal_amount' => 500.00,
            'delivery_charge' => 120.00,
            'grand_total' => 620.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $token = $this->customer->createToken('test_token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/customer/orders');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'created_at',
                        'order_status',
                        'payment_status',
                        'payment_method',
                        'grand_total',
                        'delivery_charge',
                        'courier_partner',
                        'courier_tracking_code',
                        'courier_tracking_url',
                        'courier_consignment_id',
                        'items' => [
                            '*' => [
                                'id',
                                'product_title',
                                'variant_attributes',
                                'thumbnail_image',
                                'quantity',
                                'unit_price',
                                'subtotal',
                            ],
                        ],
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $orders = $response->json('data');
        $this->assertCount(1, $orders);
        $this->assertEquals($order->order_number, $orders[0]['order_number']);
        $this->assertEquals('steadfast', $orders[0]['courier_partner']);
        $this->assertEquals('ST12345678', $orders[0]['courier_tracking_code']);
        $this->assertEquals('https://steadfast.com.bd/t/ST12345678', $orders[0]['courier_tracking_url']);
        $this->assertEquals('SF-998877', $orders[0]['courier_consignment_id']);
        $this->assertCount(1, $orders[0]['items']);
        $this->assertEquals('Premium Cotton Polo', $orders[0]['items'][0]['product_title']);
        $this->assertEquals('Navy Blue / L', $orders[0]['items'][0]['variant_attributes']);
    }
}
