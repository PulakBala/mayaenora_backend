<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Home & Kitchen',
                'slug' => 'home-kitchen',
                'description' => 'Modern cookware, organizers, smart home appliances, and kitchen tools.',
                'image' => '/images/elvoa/cat-home-kitchen.png',
                'is_featured' => true,
                'sort_order' => 1,
                'subcategories' => [
                    ['name' => 'Cookware & Bakeware', 'slug' => 'cookware'],
                    ['name' => 'Kitchen Tools & Utensils', 'slug' => 'kitchen-tools'],
                    ['name' => 'Storage & Organizers', 'slug' => 'storage-organizers'],
                    ['name' => 'Small Appliances', 'slug' => 'small-appliances'],
                    ['name' => 'Dining & Tableware', 'slug' => 'dining-tableware'],
                ],
            ],
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Smart gadgets, premium wireless audio, charging cables, and accessories.',
                'image' => '/images/elvoa/cat-electronics.png',
                'is_featured' => true,
                'sort_order' => 2,
                'subcategories' => [
                    ['name' => 'Audio & Headphones', 'slug' => 'audio-headphones'],
                    ['name' => 'Mobile Accessories & Stands', 'slug' => 'mobile-accessories'],
                    ['name' => 'Desk Lamps & LED Strips', 'slug' => 'smart-lighting'],
                    ['name' => 'Cables & Fast Chargers', 'slug' => 'cables-chargers'],
                    ['name' => 'Computer & Laptop Gear', 'slug' => 'computer-accessories'],
                ],
            ],
            [
                'name' => 'Fashion & Apparel',
                'slug' => 'fashion',
                'description' => 'Contemporary fashion, casual wear, watches, bags, and lifestyle accessories.',
                'image' => '/images/elvoa/cat-fashion.png',
                'is_featured' => true,
                'sort_order' => 3,
                'subcategories' => [
                    ['name' => "Men's Collection", 'slug' => 'mens-collection'],
                    ['name' => "Women's Collection", 'slug' => 'womens-collection'],
                    ['name' => 'Bags & Wallets', 'slug' => 'bags-wallets'],
                    ['name' => 'Shoes & Footwear', 'slug' => 'footwear'],
                    ['name' => 'Watches & Sunglasses', 'slug' => 'watches-eyewear'],
                ],
            ],
            [
                'name' => 'Kids & Toys',
                'slug' => 'kids-toys',
                'description' => 'Interactive toys, remote control stunt cars, drawing tablets, and games.',
                'image' => '/images/elvoa/cat-kids-toys.png',
                'is_featured' => true,
                'sort_order' => 4,
                'subcategories' => [
                    ['name' => 'RC Cars & Action Toys', 'slug' => 'rc-cars'],
                    ['name' => 'Educational & Tablets', 'slug' => 'educational-toys'],
                    ['name' => 'Drawing & Creative Pads', 'slug' => 'drawing-pads'],
                    ['name' => 'Board Games & Puzzles', 'slug' => 'board-games'],
                    ['name' => 'Baby & Toddler Care', 'slug' => 'baby-care'],
                ],
            ],
            [
                'name' => 'Beauty & Care',
                'slug' => 'beauty-care',
                'description' => 'Cosmetic storage, facial rollers, automatic soap dispensers, and grooming.',
                'image' => '/images/elvoa/cat-beauty-care.png',
                'is_featured' => true,
                'sort_order' => 5,
                'subcategories' => [
                    ['name' => 'Makeup Organizers & Boxes', 'slug' => 'makeup-organizers'],
                    ['name' => 'Skincare Tools & Rollers', 'slug' => 'skincare-tools'],
                    ['name' => 'Hair Styling & Brushes', 'slug' => 'hair-styling'],
                    ['name' => 'Automatic Soap Dispensers', 'slug' => 'soap-dispensers'],
                    ['name' => 'Personal Grooming', 'slug' => 'grooming'],
                ],
            ],
            [
                'name' => 'Sports & Fitness',
                'slug' => 'sports-fitness',
                'description' => 'Motivational water bottles, massage guns, yoga gear, and workout tools.',
                'image' => '/images/elvoa/cat-sports-fitness.png',
                'is_featured' => true,
                'sort_order' => 6,
                'subcategories' => [
                    ['name' => 'Motivational Water Bottles', 'slug' => 'water-bottles'],
                    ['name' => 'Massage Guns & Rollers', 'slug' => 'massage-guns'],
                    ['name' => 'Yoga Mats & Bands', 'slug' => 'yoga-bands'],
                    ['name' => 'Gym Accessories & Gloves', 'slug' => 'gym-gear'],
                    ['name' => 'Outdoor & Cycling', 'slug' => 'outdoor-cycling'],
                ],
            ],
            [
                'name' => 'Automotive',
                'slug' => 'automotive',
                'description' => 'Car phone mounts, fast USB car chargers, portable mini vacuums, and seat mats.',
                'image' => '/images/elvoa/cat-automotive.png',
                'is_featured' => false,
                'sort_order' => 7,
                'subcategories' => [
                    ['name' => 'Car Mounts & Holders', 'slug' => 'car-mounts'],
                    ['name' => 'Fast Car Chargers & Plugs', 'slug' => 'car-chargers'],
                    ['name' => 'Handheld Mini Vacuums', 'slug' => 'car-vacuums'],
                    ['name' => 'Interior Comfort & Mats', 'slug' => 'car-interior'],
                ],
            ],
            [
                'name' => 'Pet Supplies',
                'slug' => 'pet-supplies',
                'description' => 'Interactive pet toys, automatic feeder bowls, grooming brushes, and harnesses.',
                'image' => '/images/elvoa/cat-pet-supplies.png',
                'is_featured' => false,
                'sort_order' => 8,
                'subcategories' => [
                    ['name' => 'Interactive Pet Toys', 'slug' => 'pet-toys'],
                    ['name' => 'Automatic Feeders & Bowls', 'slug' => 'pet-feeders'],
                    ['name' => 'Pet Grooming & Brushes', 'slug' => 'pet-grooming'],
                    ['name' => 'Collars & Harnesses', 'slug' => 'pet-collars'],
                ],
            ],
            [
                'name' => 'Office & Stationery',
                'slug' => 'office-stationery',
                'description' => 'Ergonomic desk mats, pen holders, multi-compartment organizers, and planners.',
                'image' => '/images/elvoa/cat-office-stationery.png',
                'is_featured' => false,
                'sort_order' => 9,
                'subcategories' => [
                    ['name' => 'Desk Organizers & Trays', 'slug' => 'desk-organizers'],
                    ['name' => 'Ergonomic Desk Mats', 'slug' => 'desk-mats'],
                    ['name' => 'Notebooks & Planners', 'slug' => 'notebooks'],
                    ['name' => 'Writing Instruments', 'slug' => 'writing-instruments'],
                ],
            ],
            [
                'name' => 'More Categories',
                'slug' => 'more-categories',
                'description' => 'Smart plugs, rechargeable neck fans, aroma diffusers, and holiday gift sets.',
                'image' => '/images/elvoa/cat-more-categories.png',
                'is_featured' => false,
                'sort_order' => 10,
                'subcategories' => [
                    ['name' => 'Smart Home & Plugs', 'slug' => 'smart-home'],
                    ['name' => 'Travel Gear & Neck Fans', 'slug' => 'travel-gear'],
                    ['name' => 'Gift Sets & Festive Combos', 'slug' => 'gift-sets'],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $subcategories = $catData['subcategories'] ?? [];
            unset($catData['subcategories']);

            $parent = Category::create($catData);

            foreach ($subcategories as $subIdx => $sub) {
                Category::create([
                    'parent_id' => $parent->id,
                    'name' => $sub['name'],
                    'slug' => $sub['slug'],
                    'sort_order' => $subIdx + 1,
                    'status' => 'active',
                ]);
            }
        }
    }
}

