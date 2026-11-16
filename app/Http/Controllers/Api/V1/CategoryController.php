<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\CategoryTreeResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * Get hierarchical category tree for navigation and filters.
     */
    public function tree(Request $request): JsonResponse
    {
        $cacheKey = 'api_category_tree_v1';
        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () {
            $categories = Category::query()
                ->whereNull('parent_id')
                ->where('status', 'active')
                ->orderBy('sort_order', 'asc')
                ->with([
                    'children' => function ($query) {
                        $query->where('status', 'active')
                            ->orderBy('sort_order', 'asc')
                            ->withCount(['products' => function ($pq) {
                                $pq->where('is_published', true);
                            }]);
                    },
                ])
                ->withCount(['products' => function ($pq) {
                    $pq->where('is_published', true);
                }])
                ->get();

            return CategoryTreeResource::collection($categories)->resolve();
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get single category details by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->with([
                'parent',
                'children' => function ($query) {
                    $query->where('status', 'active')->orderBy('sort_order', 'asc');
                },
            ])
            ->withCount(['products' => function ($pq) {
                $pq->where('is_published', true);
            }])
            ->first();

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }
}

