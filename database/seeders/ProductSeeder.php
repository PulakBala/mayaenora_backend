<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::first();
        $homeCat = Category::where('slug', 'home-kitchen')->first();
        $elecCat = Category::where('slug', 'electronics')->first();
        $kidsCat = Category::where('slug', 'kids-toys')->first();
        $sportCat = Category::where('slug', 'sports-fitness')->first();

        $productsData = [
            [
                'category_id' => $homeCat?->id ?? 1,
                'brand_id' => $brand?->id,
                'title' => 'Mini Electric Food Chopper 250ml',
                'slug' => 'mini-electric-food-chopper-250ml',
                'base_sku' => 'ELV-CHOP-01',
                'short_description' => 'Rechargeable wireless garlic and vegetable mini processor with sharp stainless blades.',
                'description' => 'The Maya Enora Mini Electric Food Chopper is the ultimate portable food processor for kitchen essentials. Equipped with 3 food-grade stainless steel blades, one-touch button operation, and USB fast charging.',
                'base_cost_price' => 320.00,
                'base_selling_price' => 729.00,
                'discount_price' => 499.00,
                'discount_type' => 'fixed',
                'is_featured' => true,
                'is_published' => true,
                'image' => '/images/products/chopper.jpg',
                'variants' => [
                    ['sku' => 'ELV-CHOP-WHT', 'color' => 'White', 'size' => '250ml', 'cost' => 320.00, 'regular' => 729.00, 'sale' => 499.00, 'stock' => 85],
                    ['sku' => 'ELV-CHOP-GRN', 'color' => 'Green', 'size' => '250ml', 'cost' => 320.00, 'regular' => 729.00, 'sale' => 499.00, 'stock' => 60],
                    ['sku' => 'ELV-CHOP-PNK', 'color' => 'Pink', 'size' => '250ml', 'cost' => 320.00, 'regular' => 729.00, 'sale' => 499.00, 'stock' => 40],
                ],
            ],
            [
                'category_id' => $elecCat?->id ?? 2,
                'brand_id' => $brand?->id,
                'title' => 'TWS Wireless Earbuds (ANC)',
                'slug' => 'tws-wireless-earbuds-anc',
                'base_sku' => 'ELV-TWS-01',
                'short_description' => 'Active noise cancelling wireless earbuds with deep bass and 36-hour total battery life.',
                'description' => 'Engineered for crisp acoustics, deep bass, and active noise suppression. Features low-latency gaming mode, touch controls, IPX5 sweat resistance, and Bluetooth 5.3.',
                'base_cost_price' => 750.00,
                'base_selling_price' => 1499.00,
                'discount_price' => 1099.00,
                'discount_type' => 'fixed',
                'is_featured' => true,
                'is_published' => true,
                'image' => '/images/products/earbuds.jpg',
                'variants' => [
                    ['sku' => 'ELV-TWS-BLK', 'color' => 'Black', 'size' => 'Standard', 'cost' => 750.00, 'regular' => 1499.00, 'sale' => 1099.00, 'stock' => 120],
                    ['sku' => 'ELV-TWS-WHT', 'color' => 'White', 'size' => 'Standard', 'cost' => 750.00, 'regular' => 1499.00, 'sale' => 1099.00, 'stock' => 95],
                ],
            ],
            [
                'category_id' => $elecCat?->id ?? 2,
                'brand_id' => $brand?->id,
                'title' => 'LED Rechargeable Desk Lamp',
                'slug' => 'led-rechargeable-desk-lamp',
                'base_sku' => 'ELV-LAMP-01',
                'short_description' => 'Eye-caring foldable study desk lamp with 3 color temperatures and touch dimmer.',
                'description' => 'Protect your eyesight during long late-night study or work sessions. 360-degree flexible neck, warm/cool/neutral light modes, and integrated 2000mAh backup battery.',
                'base_cost_price' => 380.00,
                'base_selling_price' => 999.00,
                'discount_price' => 599.00,
                'discount_type' => 'fixed',
                'is_featured' => true,
                'is_published' => true,
                'image' => '/images/products/lamp.jpg',
                'variants' => [
                    ['sku' => 'ELV-LAMP-WHT', 'color' => 'White', 'size' => 'Standard', 'cost' => 380.00, 'regular' => 999.00, 'sale' => 599.00, 'stock' => 50],
                ],
            ],
            [
                'category_id' => $kidsCat?->id ?? 4,
                'brand_id' => $brand?->id,
                'title' => 'Remote Control Stunt Car',
                'slug' => 'remote-control-stunt-car',
                'base_sku' => 'ELV-CAR-01',
                'short_description' => '360° double-sided tumbling high-speed acrobatic RC stunt vehicle with LED headlights.',
                'description' => 'Extreme off-road 4WD RC vehicle that flips, rotates 360 degrees, and drives upside down across grass, carpets, and sand. Includes 2.4GHz remote and rechargeable battery pack.',
                'base_cost_price' => 550.00,
                'base_selling_price' => 899.00,
                'discount_price' => null,
                'discount_type' => null,
                'is_featured' => false,
                'is_published' => true,
                'image' => '/images/products/stuntcar.jpg',
                'variants' => [
                    ['sku' => 'ELV-CAR-BLU', 'color' => 'Blue', 'size' => 'Standard', 'cost' => 550.00, 'regular' => 899.00, 'sale' => null, 'stock' => 45],
                    ['sku' => 'ELV-CAR-RED', 'color' => 'Red', 'size' => 'Standard', 'cost' => 550.00, 'regular' => 899.00, 'sale' => null, 'stock' => 40],
                ],
            ],
            [
                'category_id' => $sportCat?->id ?? 6,
                'brand_id' => $brand?->id,
                'title' => 'Motivational Water Bottle 1 Liter',
                'slug' => 'motivational-water-bottle-1-liter',
                'base_sku' => 'ELV-BTL-01',
                'short_description' => 'BPA-free fitness bottle with time markers, motivational quotes, and pop-up straw.',
                'description' => 'Stay hydrated all day long with time hourly reminders. Crafted from durable food-safe Tritan plastic with leak-proof silicone seal ring and secure one-click flip lid.',
                'base_cost_price' => 260.00,
                'base_selling_price' => 599.00,
                'discount_price' => 449.00,
                'discount_type' => 'fixed',
                'is_featured' => true,
                'is_published' => true,
                'image' => '/images/products/waterbottle.jpg',
                'variants' => [
                    ['sku' => 'ELV-BTL-PNK', 'color' => 'Pink', 'size' => '1L', 'cost' => 260.00, 'regular' => 599.00, 'sale' => 449.00, 'stock' => 70],
                    ['sku' => 'ELV-BTL-BLU', 'color' => 'Blue', 'size' => '1L', 'cost' => 260.00, 'regular' => 599.00, 'sale' => 449.00, 'stock' => 65],
                ],
            ],
            [
                'category_id' => $homeCat?->id ?? 1,
                'brand_id' => $brand?->id,
                'title' => 'Multi-Layer Storage Box (3 Layer)',
                'slug' => 'multi-layer-storage-box-3-layer',
                'base_sku' => 'ELV-STR-01',
                'short_description' => 'Stackable organizer box with removable dividers for cosmetics, crafts, and medicines.',
                'description' => 'Keep closets and drawers tidy. High-transparency hard plastic body, sturdy snap buckles, and ergonomic carry handle.',
                'base_cost_price' => 420.00,
                'base_selling_price' => 999.00,
                'discount_price' => 699.00,
                'discount_type' => 'fixed',
                'is_featured' => false,
                'is_published' => true,
                'image' => '/images/products/storagebox.jpg',
                'variants' => [
                    ['sku' => 'ELV-STR-CLR', 'color' => 'Clear', 'size' => '3-Layer', 'cost' => 420.00, 'regular' => 999.00, 'sale' => 699.00, 'stock' => 30],
                ],
            ],
            [
                'category_id' => $homeCat?->id ?? 1,
                'brand_id' => $brand?->id,
                'title' => 'Silicone Kitchen Utensil Set (6 Pcs)',
                'slug' => 'silicone-kitchen-utensil-set',
                'base_sku' => 'ELV-UTN-01',
                'short_description' => 'Heat-resistant non-stick cooking spatulas, turner, and spoon with natural wood handles.',
                'description' => 'Food-grade premium silicone utensils safe for non-stick pans up to 230°C. Ergonomic wooden handles prevent heat transfer to hands.',
                'base_cost_price' => 190.00,
                'base_selling_price' => 599.00,
                'discount_price' => 299.00,
                'discount_type' => 'fixed',
                'is_featured' => true,
                'is_published' => true,
                'image' => '/images/products/utensils.jpg',
                'variants' => [
                    ['sku' => 'ELV-UTN-GRY', 'color' => 'Gray', 'size' => '6-Pcs', 'cost' => 190.00, 'regular' => 599.00, 'sale' => 299.00, 'stock' => 80],
                ],
            ],
            [
                'category_id' => $elecCat?->id ?? 2,
                'brand_id' => $brand?->id,
                'title' => '3 in 1 Fast Charging Cable (1.2m)',
                'slug' => '3-in-1-fast-charging-cable',
                'base_sku' => 'ELV-CBL-01',
                'short_description' => 'Braided universal charging cable featuring Type-C, Lightning, and Micro-USB tips.',
                'description' => 'High-durability nylon braided cable with reinforced aluminum alloy connector housings. Delivers up to 3.5A fast current across multiple devices simultaneously.',
                'base_cost_price' => 160.00,
                'base_selling_price' => 499.00,
                'discount_price' => 299.00,
                'discount_type' => 'fixed',
                'is_featured' => true,
                'is_published' => true,
                'image' => '/images/products/cable.jpg',
                'variants' => [
                    ['sku' => 'ELV-CBL-RED', 'color' => 'Coral Red', 'size' => '1.2m', 'cost' => 160.00, 'regular' => 499.00, 'sale' => 299.00, 'stock' => 150],
                    ['sku' => 'ELV-CBL-BLK', 'color' => 'Black', 'size' => '1.2m', 'cost' => 160.00, 'regular' => 499.00, 'sale' => 299.00, 'stock' => 120],
                ],
            ],
            [
                'category_id' => $elecCat?->id ?? 2,
                'brand_id' => $brand?->id,
                'title' => 'Foldable Desktop Phone Stand',
                'slug' => 'foldable-desktop-phone-stand',
                'base_sku' => 'ELV-STND-01',
                'short_description' => 'Angle and height adjustable aluminum desk cradle for smartphones and tablets.',
                'description' => 'Solid weighted anti-slip base with silicone protective cushions. Folds flat into pocket size for easy travel and daily desk use.',
                'base_cost_price' => 110.00,
                'base_selling_price' => 299.00,
                'discount_price' => 199.00,
                'discount_type' => 'fixed',
                'is_featured' => false,
                'is_published' => true,
                'image' => '/images/products/phonestand.jpg',
                'variants' => [
                    ['sku' => 'ELV-STND-SLV', 'color' => 'Silver', 'size' => 'Standard', 'cost' => 110.00, 'regular' => 299.00, 'sale' => 199.00, 'stock' => 110],
                    ['sku' => 'ELV-STND-BLK', 'color' => 'Black', 'size' => 'Standard', 'cost' => 110.00, 'regular' => 299.00, 'sale' => 199.00, 'stock' => 90],
                ],
            ],
            [
                'category_id' => $homeCat?->id ?? 1,
                'brand_id' => $brand?->id,
                'title' => 'Portable Personal Blender 400ml',
                'slug' => 'portable-personal-blender-400ml',
                'base_sku' => 'ELV-BLND-01',
                'short_description' => 'USB rechargeable smoothie maker and juicer cup with 6 stainless steel blades.',
                'description' => 'Blend fresh fruit juices and protein shakes anywhere. High-speed 18,000 RPM motor, magnetic safety sensor, and 400ml BPA-free bottle with sipping lid.',
                'base_cost_price' => 520.00,
                'base_selling_price' => 899.00,
                'discount_price' => null,
                'discount_type' => null,
                'is_featured' => true,
                'is_published' => true,
                'image' => '/images/products/blender.jpg',
                'variants' => [
                    ['sku' => 'ELV-BLND-PNK', 'color' => 'Pink', 'size' => '400ml', 'cost' => 520.00, 'regular' => 899.00, 'sale' => null, 'stock' => 60],
                    ['sku' => 'ELV-BLND-WHT', 'color' => 'White', 'size' => '400ml', 'cost' => 520.00, 'regular' => 899.00, 'sale' => null, 'stock' => 55],
                ],
            ],
        ];

        foreach ($productsData as $prod) {
            $variants = $prod['variants'] ?? [];
            $image = $prod['image'] ?? null;
            unset($prod['variants'], $prod['image']);

            $product = Product::create($prod);

            // Primary Image
            if ($image) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $image,
                    'alt_text' => $product->title,
                    'is_primary' => true,
                    'sort_order' => 1,
                ]);
            }

            // Variants & Initial Stock Logs
            foreach ($variants as $var) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'variant_sku' => $var['sku'],
                    'size' => $var['size'],
                    'color' => $var['color'],
                    'cost_price' => $var['cost'],
                    'regular_price' => $var['regular'],
                    'sale_price' => $var['sale'],
                    'image' => $image,
                    'stock_quantity' => $var['stock'],
                    'low_stock_threshold' => 5,
                    'status' => 'active',
                ]);

                InventoryLog::create([
                    'product_variant_id' => $variant->id,
                    'transaction_type' => 'purchase',
                    'quantity_change' => $var['stock'],
                    'remaining_stock' => $var['stock'],
                    'remarks' => 'Initial inventory intake seed',
                ]);
            }
        }
    }
}

