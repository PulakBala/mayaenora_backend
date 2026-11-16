<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use Illuminate\Http\JsonResponse;

class ThemeController extends Controller
{
    /**
     * Get active theme & appearance colors.
     */
    public function show(): JsonResponse
    {
        $settings = ThemeSetting::getSettings();

        return response()->json([
            'success' => true,
            'data' => [
                'header_bg_color' => $settings->header_bg_color ?: '#ffffff',
                'header_text_color' => $settings->header_text_color ?: '#111827',
                'footer_bg_color' => $settings->footer_bg_color ?: '#111827',
                'footer_text_color' => $settings->footer_text_color ?: '#d1d5db',
                'button_bg_color' => $settings->button_bg_color ?: '#FF5B37',
                'button_hover_color' => $settings->button_hover_color ?: '#eb4e2a',
                'button_text_color' => $settings->button_text_color ?: '#ffffff',
                'body_bg_color' => $settings->body_bg_color ?: '#ffffff',
                'is_active' => (bool) $settings->is_active,
            ],
        ])->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
