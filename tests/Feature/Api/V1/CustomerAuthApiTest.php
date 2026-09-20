<?php

namespace Tests\Feature\Api\V1;

use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test successful registration.
     */
    public function test_customer_can_register_and_receive_token(): void
    {
        $payload = [
            'name' => 'John Doe',
            'phone' => '01711223344',
            'email' => 'john.doe@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Account registered successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'role',
                    ],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'phone' => '01711223344',
            'name' => 'John Doe',
        ]);
    }

    /**
     * Test registration failure on duplicate phone/email.
     */
    public function test_registration_fails_with_duplicate_phone_or_email(): void
    {
        $customerRole = Role::where('slug', 'customer')->first();

        User::create([
            'role_id' => $customerRole?->id ?? 1,
            'name' => 'Existing User',
            'phone' => '01899887766',
            'email' => 'existing@example.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'phone' => '01899887766',
            'email' => 'existing@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'email']);
    }

    /**
     * Test login with email.
     */
    public function test_customer_can_login_with_email_and_password(): void
    {
        $user = User::create([
            'role_id' => 1,
            'name' => 'Email Login User',
            'phone' => '01900112233',
            'email' => 'login_email@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'login_email@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);
    }

    /**
     * Test login with phone.
     */
    public function test_customer_can_login_with_phone_and_password(): void
    {
        $user = User::create([
            'role_id' => 1,
            'name' => 'Phone Login User',
            'phone' => '01500998877',
            'email' => 'login_phone@example.com',
            'password' => Hash::make('securepass'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '01500998877',
            'password' => 'securepass',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'phone' => '01500998877',
                    ],
                ],
            ]);
    }

    /**
     * Test login failure with wrong password.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        User::create([
            'role_id' => 1,
            'name' => 'Wrong Password User',
            'phone' => '01300112233',
            'email' => 'wrong_pw@example.com',
            'password' => Hash::make('correctpassword'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'wrong_pw@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test logout revokes token.
     */
    public function test_customer_can_logout_and_revoke_token(): void
    {
        $user = User::create([
            'role_id' => 1,
            'name' => 'Logout User',
            'phone' => '01600112233',
            'email' => 'logout_user@example.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);

        $this->assertCount(0, $user->tokens);
    }

    /**
     * Test unauthenticated access to profile returns 401.
     */
    public function test_unauthenticated_request_to_profile_returns_401(): void
    {
        $response = $this->getJson('/api/v1/user/profile');
        $response->assertStatus(401);
    }

    /**
     * Test authenticated customer can fetch own profile.
     */
    public function test_customer_can_fetch_own_profile(): void
    {
        $uniquePhone = '017' . rand(10000000, 99999999);
        $uniqueEmail = 'prof_' . uniqid() . '@example.com';

        $user = User::create([
            'role_id' => 1,
            'name' => 'Profile User',
            'phone' => $uniquePhone,
            'email' => $uniqueEmail,
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Profile User',
                    'email' => $uniqueEmail,
                    'phone' => $uniquePhone,
                ],
            ]);
    }

    /**
     * Test customer cannot access another customer's order (strictly returns 403).
     */
    public function test_customer_cannot_access_another_customers_order(): void
    {
        $user1 = User::create([
            'role_id' => 1,
            'name' => 'Customer One',
            'phone' => '017' . rand(10000000, 99999999),
            'email' => 'u1_' . uniqid() . '@example.com',
            'password' => Hash::make('pass'),
            'status' => 'active',
        ]);

        $user2 = User::create([
            'role_id' => 1,
            'name' => 'Customer Two',
            'phone' => '017' . rand(10000000, 99999999),
            'email' => 'u2_' . uniqid() . '@example.com',
            'password' => Hash::make('pass'),
            'status' => 'active',
        ]);

        $order = Order::create([
            'order_number' => 'ELV-ORD-TEST-' . uniqid(),
            'user_id' => $user1->id,
            'customer_name' => 'Customer One',
            'customer_phone' => $user1->phone,
            'shipping_district' => 'Dhaka',
            'shipping_address' => 'Test Address 1',
            'subtotal_amount' => 500,
            'delivery_charge' => 60,
            'grand_total' => 560,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        // User2 attempts to view User1's order
        $token2 = $user2->createToken('user2-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token2}")
            ->getJson("/api/v1/user/orders/{$order->order_number}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test customer address management (CRUD).
     */
    public function test_customer_can_manage_addresses(): void
    {
        $uniquePhone = '017' . rand(10000000, 99999999);
        $user = User::create([
            'role_id' => 1,
            'name' => 'Address Tester',
            'phone' => $uniquePhone,
            'email' => 'addr_' . uniqid() . '@example.com',
            'password' => Hash::make('pass'),
            'status' => 'active',
        ]);

        $token = $user->createToken('token')->plainTextToken;

        // 1. Create Address
        $createRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/user/addresses', [
                'recipient_name' => 'Address Tester',
                'recipient_phone' => $uniquePhone,
                'district' => 'Dhaka',
                'city_zone' => 'Mirpur',
                'address' => 'House 12, Road 5',
                'postal_code' => '1216',
                'is_default' => true,
            ]);

        $createRes->assertStatus(201);
        $addressId = $createRes->json('data.id');

        // 2. Update Address
        $updateRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/user/addresses/{$addressId}", [
                'recipient_name' => 'Address Tester Updated',
                'recipient_phone' => $uniquePhone,
                'district' => 'Dhaka',
                'address' => 'House 14, Road 5 Updated',
            ]);

        $updateRes->assertStatus(200)
            ->assertJson([
                'data' => [
                    'recipient_name' => 'Address Tester Updated',
                    'address' => 'House 14, Road 5 Updated',
                ],
            ]);

        // 3. Delete Address
        $deleteRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/user/addresses/{$addressId}");

        $deleteRes->assertStatus(200);
        $this->assertDatabaseMissing('customer_addresses', ['id' => $addressId]);
    }

    /**
     * Test authenticated checkout binds order to user.
     */
    public function test_authenticated_checkout_binds_order_to_user(): void
    {
        $uniquePhone = '017' . rand(10000000, 99999999);
        $uniqueEmail = 'chk_' . uniqid() . '@example.com';

        $user = User::create([
            'role_id' => 1,
            'name' => 'Checkout Customer',
            'phone' => $uniquePhone,
            'email' => $uniqueEmail,
            'password' => Hash::make('pass'),
            'status' => 'active',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $variant = ProductVariant::where('stock_quantity', '>', 5)->first();
        if (! $variant) {
            $product = Product::first();
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'variant_sku' => 'TEST-CUST-VAR',
                'regular_price' => 500,
                'stock_quantity' => 10,
                'status' => 'active',
            ]);
        }

        $payload = [
            'customer_name' => 'Checkout Customer',
            'customer_phone' => $uniquePhone,
            'customer_email' => $uniqueEmail,
            'shipping_district' => 'Dhaka',
            'shipping_address' => 'Gulshan 2, Road 45',
            'postal_code' => '1212',
            'delivery_method' => 'inside-dhaka',
            'payment_method' => 'cod',
            'items' => [
                [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/checkout/order', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $orderNumber = $response->json('data.order_number');
        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'user_id' => $user->id,
        ]);
    }
}
