<?php

namespace Tests\Feature\Filament;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test guest is redirected to admin login page.
     */
    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    /**
     * Test customer cannot access admin panel (403 Forbidden).
     */
    public function test_customer_cannot_access_admin_panel(): void
    {
        $customerRole = Role::where('slug', 'customer')->first();

        $customer = User::create([
            'role_id' => $customerRole?->id,
            'name' => 'Regular Customer',
            'email' => 'customer_test_' . uniqid() . '@example.com',
            'phone' => '018' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($customer)->get('/admin');

        $response->assertForbidden();
    }

    /**
     * Test super-admin can access admin dashboard.
     */
    public function test_super_admin_can_access_admin_dashboard(): void
    {
        $adminRole = Role::whereIn('slug', ['admin', 'super-admin'])->first();
        $this->assertNotNull($adminRole);

        $admin = User::create([
            'role_id' => $adminRole->id,
            'name' => 'Admin User',
            'email' => 'admin_test_' . uniqid() . '@example.com',
            'phone' => '019' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertSuccessful();
    }

    /**
     * Test admin can access resource list pages.
     */
    public function test_admin_can_access_key_filament_resources(): void
    {
        $adminRole = Role::whereIn('slug', ['admin', 'super-admin'])->first();
        $this->assertNotNull($adminRole);

        $admin = User::create([
            'role_id' => $adminRole->id,
            'name' => 'Admin Tester',
            'email' => 'admin_res_' . uniqid() . '@example.com',
            'phone' => '019' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $routes = [
            '/admin/categories',
            '/admin/brands',
            '/admin/products',
            '/admin/coupons',
            '/admin/banners',
            '/admin/orders',
            '/admin/suppliers',
            '/admin/purchases',
            '/admin/inventory-logs',
            '/admin/expense-categories',
            '/admin/expenses',
            '/admin/courier-settings',
            '/admin/payment-settings',
            '/admin/payments',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get($route);
            $response->assertSuccessful();
        }
    }

    /**
     * Test creating a product with image gallery and rendering in admin table.
     */
    public function test_can_create_product_with_images_and_render_in_table(): void
    {
        $role = Role::where('name', 'Super Admin')->orWhere('slug', 'super-admin')->first()
            ?? Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $admin = User::firstOrCreate(['email' => 'admin@elvoa.com'], [
            'name' => 'Admin User',
            'phone' => '01700000001',
            'role_id' => $role->id,
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $category = Category::firstOrCreate(['slug' => 'test-cat'], [
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'Test Smart Product',
            'slug' => 'test-smart-product-' . uniqid(),
            'base_sku' => 'TEST-' . uniqid(),
            'base_selling_price' => 750.00,
            'is_published' => true,
        ]);

        $image = $product->images()->create([
            'image_url' => 'products/gallery/sample.jpg',
            'alt_text' => 'Sample Image',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'image_url' => 'products/gallery/sample.jpg',
        ]);

        $response = $this->actingAs($admin)->get("/admin/products/{$product->id}/edit");
        $response->assertSuccessful();
        $response->assertSee('Test Smart Product');
    }
}
