<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FooterSection;
use App\Models\FooterSetting;
use App\Models\ThemeSetting;
use Illuminate\Http\JsonResponse;

class FooterController extends Controller
{
    /**
     * Get all active footer settings, social links, and categorized footer link columns.
     */
    public function index(): JsonResponse
    {
        $settings = FooterSetting::getSettings();

        // Filter active social links
        $activeSocials = collect($settings->social_links ?? [])
            ->filter(fn ($item) => ! empty($item['is_active']) && ! empty($item['url']))
            ->values()
            ->all();

        $sections = FooterSection::active()
            ->with(['activeLinks'])
            ->get()
            ->map(function ($section) {
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'sort_order' => $section->sort_order,
                    'links' => $section->activeLinks->map(function ($link) {
                        return [
                            'id' => $link->id,
                            'title' => $link->title,
                            'url' => $link->url,
                            'open_in_new_tab' => (bool) $link->open_in_new_tab,
                            'sort_order' => $link->sort_order,
                        ];
                    })->values(),
                ];
            });

        $theme = ThemeSetting::getSettings();

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => [
                    'brand_bio' => $settings->brand_bio,
                    'copyright_text' => $settings->copyright_text,
                    'bottom_slogan' => $settings->bottom_slogan,
                    'newsletter_title' => $settings->newsletter_title,
                    'newsletter_subtitle' => $settings->newsletter_subtitle,
                    'newsletter_enabled' => (bool) $settings->newsletter_enabled,
                    'social_links' => $activeSocials,
                    'contact_email' => $settings->contact_email,
                    'contact_phone' => $settings->contact_phone,
                    'contact_address' => $settings->contact_address,
                ],
                'sections' => $sections,
                'theme' => [
                    'header_bg_color' => $theme->header_bg_color ?: '#ffffff',
                    'header_text_color' => $theme->header_text_color ?: '#111827',
                    'footer_bg_color' => $theme->footer_bg_color ?: '#111827',
                    'footer_text_color' => $theme->footer_text_color ?: '#d1d5db',
                    'button_bg_color' => $theme->button_bg_color ?: '#FF5B37',
                    'button_hover_color' => $theme->button_hover_color ?: '#eb4e2a',
                    'button_text_color' => $theme->button_text_color ?: '#ffffff',
                    'body_bg_color' => $theme->body_bg_color ?: '#ffffff',
                    'is_active' => (bool) $theme->is_active,
                ],
            ],
        ]);
    }
}
