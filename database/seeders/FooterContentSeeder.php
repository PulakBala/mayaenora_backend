<?php

namespace Database\Seeders;

use App\Models\FooterLink;
use App\Models\FooterSection;
use App\Models\FooterSetting;
use Illuminate\Database\Seeder;

class FooterContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Footer Settings
        FooterSetting::firstOrCreate([], [
            'brand_bio' => 'Your trusted online store for everyday essentials, trending products and more.',
            'copyright_text' => '© 2026 ELVOA. All rights reserved.',
            'bottom_slogan' => 'Shop Better. Live Smarter.',
            'newsletter_title' => 'Subscribe to our newsletter',
            'newsletter_subtitle' => 'Get the latest deals and updates.',
            'newsletter_enabled' => true,
            'social_links' => [
                ['platform' => 'facebook', 'title' => 'Facebook', 'url' => 'https://facebook.com', 'is_active' => true],
                ['platform' => 'instagram', 'title' => 'Instagram', 'url' => 'https://instagram.com', 'is_active' => true],
                ['platform' => 'youtube', 'title' => 'YouTube', 'url' => 'https://youtube.com', 'is_active' => true],
                ['platform' => 'tiktok', 'title' => 'TikTok', 'url' => 'https://tiktok.com', 'is_active' => true],
            ],
            'contact_email' => 'support@elvoa.com',
            'contact_phone' => '+880 9610-000000',
            'contact_address' => 'House #12, Road #4, Dhanmondi, Dhaka-1205, Bangladesh',
        ]);

        // 2. Sections and Links
        $sections = [
            [
                'title' => 'Shop',
                'sort_order' => 1,
                'links' => [
                    ['title' => 'All Products', 'url' => '/shop', 'sort_order' => 1],
                    ['title' => 'New Arrivals', 'url' => '/new-arrivals', 'sort_order' => 2],
                    ['title' => 'Best Sellers', 'url' => '/best-sellers', 'sort_order' => 3],
                    ['title' => 'Deals', 'url' => '/deals', 'sort_order' => 4],
                    ['title' => 'Under ৳499', 'url' => '/under-499', 'sort_order' => 5],
                ],
            ],
            [
                'title' => 'Customer Care',
                'sort_order' => 2,
                'links' => [
                    ['title' => 'Track Order', 'url' => '/track-order', 'sort_order' => 1],
                    ['title' => 'Return Policy', 'url' => '/return-policy', 'sort_order' => 2],
                    ['title' => 'Shipping Info', 'url' => '/shipping-info', 'sort_order' => 3],
                    ['title' => 'FAQ', 'url' => '/faq', 'sort_order' => 4],
                    ['title' => 'Contact Us', 'url' => '/contact', 'sort_order' => 5],
                ],
            ],
            [
                'title' => 'About ELVOA',
                'sort_order' => 3,
                'links' => [
                    ['title' => 'Our Story', 'url' => '/about', 'sort_order' => 1],
                    ['title' => 'Terms & Conditions', 'url' => '/terms', 'sort_order' => 2],
                    ['title' => 'Privacy Policy', 'url' => '/privacy', 'sort_order' => 3],
                ],
            ],
        ];

        foreach ($sections as $secData) {
            $section = FooterSection::firstOrCreate(
                ['title' => $secData['title']],
                ['sort_order' => $secData['sort_order'], 'is_active' => true]
            );

            foreach ($secData['links'] as $linkData) {
                FooterLink::firstOrCreate(
                    [
                        'footer_section_id' => $section->id,
                        'title' => $linkData['title'],
                    ],
                    [
                        'url' => $linkData['url'],
                        'sort_order' => $linkData['sort_order'],
                        'open_in_new_tab' => false,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
