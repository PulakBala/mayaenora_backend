<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicStoreApiTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * Test hierarchical category tree endpoint.
     */
    public function test_can_fetch_category_tree(): void
    {
        $response = $this->getJson('/api/v1/categories/tree');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'icon_image',
                        'subcategories' => [
                            '*' => [
                                'id',
                                'name',
                                'slug',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('data'));
    }

    /**
     * Test single category by slug.
     */
    public function test_can_fetch_single_category(): void
    {
        $category = Category::whereNull('parent_id')->first();
        $this->assertNotNull($category);

        $response = $this->getJson("/api/v1/categories/{$category->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', $category->slug);
    }

    /**
     * Test product listing and pagination.
     */
    public function test_can_fetch_product_list(): void
    {
        $response = $this->getJson('/api/v1/products?per_page=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'sku',
                        'price',
                        'image',
                        'gallery',
                        'in_stock',
                        'category',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertLessThanOrEqual(5, count($response->json('data')));
    }

    /**
     * Test product listing with filters.
     */
    public function test_can_filter_products_by_category_and_price(): void
    {
        $response = $this->getJson('/api/v1/products?category_slug=home-kitchen&min_price=100&max_price=1000');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test single product detail.
     */
    public function test_can_fetch_product_detail(): void
    {
        $product = Product::where('is_published', true)->first();
        $this->assertNotNull($product);

        $response = $this->getJson("/api/v1/products/{$product->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'price',
                    'variants',
                    'reviews',
                    'category',
                ],
            ]);
    }

    /**
     * Test coupon validation endpoint.
     */
    public function test_can_validate_active_coupon(): void
    {
        $response = $this->postJson('/api/v1/cart/validate-coupon', [
            'code' => 'ELVOA10',
            'subtotal' => 1500,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'ELVOA10');
    }

    /**
     * Test complete checkout order flow with instant account creation, stock decrement and address binding.
     */
    public function test_can_place_checkout_order_with_guest_to_account_and_stock_reduction(): void
    {
        $variant = ProductVariant::where('stock_quantity', '>', 5)->first();
        $this->assertNotNull($variant);

        $initialStock = $variant->stock_quantity;
        $orderQty = 2;
        $phone = '017' . rand(10000000, 99999999);
        $email = 'guest_' . uniqid() . '@example.com';

        $payload = [
            'customer_name' => 'Fahim Rahman',
            'customer_phone' => $phone,
            'customer_email' => $email,
            'password' => 'Pass123456!',
            'shipping_district' => 'Dhaka',
            'shipping_address' => 'House 42, Road 11, Banani, Dhaka',
            'postal_code' => '1213',
            'delivery_method' => 'inside-dhaka',
            'payment_method' => 'cod',
            'coupon_code' => 'ELVOA10',
            'customer_notes' => 'Please deliver in afternoon.',
            'items' => [
                [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $orderQty,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/checkout/order', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('auth.is_new_user', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'order_number',
                    'customer',
                    'shipping',
                    'pricing',
                    'items',
                ],
                'auth' => [
                    'token',
                    'is_new_user',
                ],
            ]);

        // Verify account creation in users table
        $createdUser = User::where('phone', $phone)->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('Fahim Rahman', $createdUser->name);

        // Verify stock quantity was decremented
        $variant->refresh();
        $this->assertEquals($initialStock - $orderQty, $variant->stock_quantity);

        // Verify order items and inventory logs
        $this->assertDatabaseHas('orders', [
            'customer_phone' => $phone,
            'shipping_district' => 'Dhaka',
            'order_status' => 'pending',
        ]);

        $this->assertDatabaseHas('inventory_logs', [
            'product_variant_id' => $variant->id,
            'transaction_type' => 'sale',
            'quantity_change' => -$orderQty,
            'remaining_stock' => $initialStock - $orderQty,
        ]);
    }

    /**
     * Test checkout rejects when requested quantity exceeds inventory stock.
     */
    public function test_checkout_fails_when_quantity_exceeds_stock(): void
    {
        $variant = ProductVariant::first();
        $this->assertNotNull($variant);

        // Temporarily set low stock to test shortage
        $variant->update(['stock_quantity' => 2]);

        $response = $this->postJson('/api/v1/checkout/order', [
            'customer_name' => 'Stock Test User',
            'customer_phone' => '018' . rand(10000000, 99999999),
            'shipping_district' => 'Dhaka',
            'shipping_address' => 'Test Address',
            'delivery_method' => 'inside-dhaka',
            'payment_method' => 'cod',
            'items' => [
                [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 5,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /**
     * Test coupon fails validation if subtotal is below min spend.
     */
    public function test_coupon_fails_when_below_min_spend(): void
    {
        $response = $this->postJson('/api/v1/cart/validate-coupon', [
            'code' => 'WELCOME50',
            'subtotal' => 200, // min_spend for WELCOME50 is 1000
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}

