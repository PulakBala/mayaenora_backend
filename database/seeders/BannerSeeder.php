<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = [
            [
                'title' => 'Smart Finds for a Better Everyday',
                'tag' => 'SMART HOME & LIVING',
                'subtitle' => 'Quality products. Great prices. Only at ELVOA.',
                'image' => 'banners/hero-slide-1.jpg',
                'link_url' => '/shop?category=home-kitchen',
                'button_text' => 'Shop Now',
                'placement' => 'hero_slider',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Crisp Audio & Smart Desk Gear',
                'tag' => 'NEXT-GEN ELECTRONICS',
                'subtitle' => 'Premium ANC earbuds, wireless tech, and rechargeable lighting designed for peak productivity.',
                'image' => 'banners/hero-slide-2.jpg',
                'link_url' => '/shop?category=electronics',
                'button_text' => 'Explore Tech',
                'placement' => 'hero_slider',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Curated Fashion & Lifestyle Drops',
                'tag' => 'MODERN ESSENTIALS',
                'subtitle' => 'Minimalist fashion crafted for everyday comfort, confidence, and effortless style.',
                'image' => 'banners/hero-slide-3.jpg',
                'link_url' => '/shop?category=fashion',
                'button_text' => 'View Collection',
                'placement' => 'hero_slider',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $data) {
            Banner::updateOrCreate(
                ['title' => $data['title'], 'placement' => $data['placement']],
                $data
            );
        }
    }
}
