<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    use HasFactory;

    protected $table = 'theme_settings';

    protected $fillable = [
        'header_bg_color',
        'header_text_color',
        'footer_bg_color',
        'footer_text_color',
        'button_bg_color',
        'button_hover_color',
        'button_text_color',
        'body_bg_color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get or create singleton theme settings record.
     */
    public static function getSettings(): self
    {
        return static::firstOrCreate([], [
            'header_bg_color' => '#4A0E4E',
            'header_text_color' => '#ffffff',
            'footer_bg_color' => '#24062B',
            'footer_text_color' => '#EADFD6',
            'button_bg_color' => '#E87A15',
            'button_hover_color' => '#C96109',
            'button_text_color' => '#ffffff',
            'body_bg_color' => '#FAF7F5',
            'is_active' => true,
        ]);
    }
}
