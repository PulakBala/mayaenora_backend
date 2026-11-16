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
                'title' => 'Exquisite Handcrafted Gowns & Silk Sarees',
                'tag' => 'THE ART OF SUBTLE LUXURY',
                'subtitle' => 'Step into effortless grace. Discover regal micro-velvet gowns, pure Banarasi Katan silks, and contemporary evening drapes.',
                'image' => '/images/products/dress-01-velvet-gown.jpg',
                'link_url' => '/shop?category=womens-collection',
                'button_text' => 'Explore Luxury Drapes',
                'placement' => 'hero_slider',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Pure Katan Silk & Bridal Lehengas',
                'tag' => 'HERITAGE MEETS CONTEMPORARY',
                'subtitle' => 'Hand-embellished zari threadwork, delicate resham pallus, and featherlight organzas made for unforgettable celebrations.',
                'image' => '/images/products/dress-04-crimson-lehenga.jpg',
                'link_url' => '/shop?category=fashion',
                'button_text' => 'Shop Bridal & Festive',
                'placement' => 'hero_slider',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Liquid Satin Gowns & Draped Silhouettes',
                'tag' => 'CURATED EVENING WEAR',
                'subtitle' => 'Sophisticated modern silhouettes designed to accentuate your elegance at every gala and intimate celebration.',
                'image' => '/images/products/dress-09-satin-gown.jpg',
                'link_url' => '/shop',
                'button_text' => 'View Evening Collection',
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

