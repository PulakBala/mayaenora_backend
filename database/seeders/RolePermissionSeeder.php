<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $customerRole = Role::create([
            'id' => 1,
            'name' => 'Customer',
            'slug' => 'customer',
            'description' => 'Regular online shopping customer',
        ]);

        $adminRole = Role::create([
            'id' => 2,
            'name' => 'Super Admin',
            'slug' => 'admin',
            'description' => 'Full administrative access across all business modules',
        ]);

        $managerRole = Role::create([
            'id' => 3,
            'name' => 'Operations Manager',
            'slug' => 'manager',
            'description' => 'Manages catalog, orders, logistics, and supply chain',
        ]);

        $staffRole = Role::create([
            'id' => 4,
            'name' => 'Support Staff',
            'slug' => 'staff',
            'description' => 'Manages order processing, customer queries, and inventory updates',
        ]);

        // 2. Permissions
        $permissions = [
            // Catalog
            ['name' => 'View Products', 'slug' => 'view-products', 'module' => 'Catalog'],
            ['name' => 'Create Products', 'slug' => 'create-products', 'module' => 'Catalog'],
            ['name' => 'Edit Products', 'slug' => 'edit-products', 'module' => 'Catalog'],
            ['name' => 'Delete Products', 'slug' => 'delete-products', 'module' => 'Catalog'],
            ['name' => 'Manage Categories', 'slug' => 'manage-categories', 'module' => 'Catalog'],
            ['name' => 'Manage Brands', 'slug' => 'manage-brands', 'module' => 'Catalog'],

            // Orders
            ['name' => 'View Orders', 'slug' => 'view-orders', 'module' => 'Orders'],
            ['name' => 'Process Orders', 'slug' => 'process-orders', 'module' => 'Orders'],
            ['name' => 'Update Order Status', 'slug' => 'update-order-status', 'module' => 'Orders'],
            ['name' => 'Cancel Orders', 'slug' => 'cancel-orders', 'module' => 'Orders'],
            ['name' => 'Manage Returns & Refunds', 'slug' => 'manage-returns', 'module' => 'Orders'],

            // Supply Chain & Inventory
            ['name' => 'Manage Suppliers', 'slug' => 'manage-suppliers', 'module' => 'Inventory'],
            ['name' => 'Manage Purchases', 'slug' => 'manage-purchases', 'module' => 'Inventory'],
            ['name' => 'Adjust Stock', 'slug' => 'adjust-stock', 'module' => 'Inventory'],
            ['name' => 'View Stock Logs', 'slug' => 'view-stock-logs', 'module' => 'Inventory'],

            // Marketing
            ['name' => 'Manage Coupons', 'slug' => 'manage-coupons', 'module' => 'Marketing'],
            ['name' => 'Manage Banners', 'slug' => 'manage-banners', 'module' => 'Marketing'],
            ['name' => 'Moderate Reviews', 'slug' => 'moderate-reviews', 'module' => 'Marketing'],

            // Financial & Expenses
            ['name' => 'View Analytics & Reports', 'slug' => 'view-analytics', 'module' => 'Reports'],
            ['name' => 'Manage Expenses', 'slug' => 'manage-expenses', 'module' => 'Finance'],

            // System
            ['name' => 'Manage Users', 'slug' => 'manage-users', 'module' => 'Users'],
            ['name' => 'Manage Roles & Permissions', 'slug' => 'manage-roles', 'module' => 'System'],
            ['name' => 'View Audit Logs', 'slug' => 'view-audit-logs', 'module' => 'System'],
        ];

        $allPermissionIds = [];
        foreach ($permissions as $perm) {
            $created = Permission::create($perm);
            $allPermissionIds[] = $created->id;
        }

        // Admin gets all permissions
        $adminRole->permissions()->sync($allPermissionIds);

        // Manager gets operational permissions
        $managerPermissions = Permission::whereIn('slug', [
            'view-products', 'create-products', 'edit-products', 'manage-categories', 'manage-brands',
            'view-orders', 'process-orders', 'update-order-status', 'manage-returns',
            'manage-suppliers', 'manage-purchases', 'adjust-stock', 'view-stock-logs',
            'manage-coupons', 'manage-banners', 'moderate-reviews', 'view-analytics', 'manage-expenses',
        ])->pluck('id');
        $managerRole->permissions()->sync($managerPermissions);

        // Staff gets order and stock permissions
        $staffPermissions = Permission::whereIn('slug', [
            'view-products', 'view-orders', 'process-orders', 'update-order-status',
            'adjust-stock', 'view-stock-logs', 'moderate-reviews',
        ])->pluck('id');
        $staffRole->permissions()->sync($staffPermissions);

        // 3. Seed Users
        // Super Admin
        User::create([
            'role_id' => $adminRole->id,
            'name' => 'ELVOA Administrator',
            'email' => 'admin@elvoa.com',
            'phone' => '01700000000',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'password' => Hash::make('password123'),
            'status' => 'active',
            'metadata' => ['department' => 'Executive Office', 'access_level' => 'root'],
        ]);

        // Operations Manager
        User::create([
            'role_id' => $managerRole->id,
            'name' => 'Sabbir Rahman',
            'email' => 'manager@elvoa.com',
            'phone' => '01711111111',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'password' => Hash::make('password123'),
            'status' => 'active',
            'metadata' => ['department' => 'Operations & Logistics'],
        ]);

        // Customer
        User::create([
            'role_id' => $customerRole->id,
            'name' => 'Tanvir Ahmed',
            'email' => 'tanvir.ahmed@example.com',
            'phone' => '01712345678',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'password' => Hash::make('password123'),
            'status' => 'active',
            'metadata' => ['preferred_delivery_zone' => 'Dhaka North', 'reward_points' => 240],
        ]);
    }
}

