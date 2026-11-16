<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BannerController extends Controller
{
    /**
     * Get active banners, optionally filtered by placement.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Banner::active()->orderBy('sort_order', 'asc')->orderBy('id', 'asc');

        if ($request->filled('placement')) {
            $query->placement($request->query('placement'));
        }

        $banners = $query->get()->map(function (Banner $banner) {
            $img = $banner->image ?: $banner->desktop_image;
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'tag' => $banner->tag,
                'subtitle' => $banner->subtitle,
                'image' => $img,
                'mobile_image' => $banner->mobile_image,
                'link_url' => $banner->link_url ?: $banner->target_url,
                'button_text' => $banner->button_text ?: 'Shop Now',
                'placement' => $banner->placement ?: ($banner->position === 'top_slider' ? 'hero_slider' : $banner->position),
                'sort_order' => $banner->sort_order,
            ];
        })->all();

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }
}

