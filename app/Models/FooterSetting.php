<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FooterSetting extends Model
{
    use HasFactory;

    protected $table = 'footer_settings';

    protected $fillable = [
        'brand_bio',
        'copyright_text',
        'bottom_slogan',
        'newsletter_title',
        'newsletter_subtitle',
        'newsletter_enabled',
        'social_links',
        'contact_email',
        'contact_phone',
        'contact_address',
    ];

    protected $casts = [
        'newsletter_enabled' => 'boolean',
        'social_links' => 'array',
    ];

    /**
     * Get or create singleton footer settings record.
     */
    public static function getSettings(): self
    {
        return static::firstOrCreate([], [
            'brand_bio' => 'Your trusted online store for everyday essentials, trending products and more.',
            'copyright_text' => '© 2026 Maya Enora. All rights reserved.',
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
            'contact_email' => 'support@mayaenora.com',
            'contact_phone' => '+880 9610-000000',
            'contact_address' => 'House #12, Road #4, Dhanmondi, Dhaka-1205, Bangladesh',
        ]);
    }
}
