<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductIndexRequest;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductListResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Get optimized, consolidated home page product sections in a single request.
     * Caches formatted data to ensure instant sub-10ms response times.
     */
    public function homeSections(): JsonResponse
    {
        $cacheKey = 'api_home_sections_v1';
        $data = Cache::remember($cacheKey, now()->addMinutes(15), function () {
            $withRelations = [
                'category:id,name,slug',
                'brand:id,name,slug',
                'primaryImage',
                'images',
                'variants',
                'approvedReviews',
            ];

            $baseQuery = fn () => Product::query()
                ->where('is_published', true)
                ->with($withRelations);

            // 1. Trending / Hot Products (Featured items)
            $trending = $baseQuery()
                ->where('is_featured', true)
                ->orderByDesc('id')
                ->take(8)
                ->get();

            if ($trending->isEmpty()) {
                $trending = $baseQuery()
                    ->orderByDesc('id')
                    ->take(8)
                    ->get();
            }

            // 2. Festive & Wedding Edit
            $festiveCategory = Category::where('slug', 'womens-collection')
                ->orWhere('slug', 'festive')
                ->first();

            if ($festiveCategory) {
                $categoryIds = $festiveCategory->children()->pluck('id')->push($festiveCategory->id);
                $festive = $baseQuery()
                    ->whereIn('category_id', $categoryIds)
                    ->orderByDesc('id')
                    ->take(8)
                    ->get();
            } else {
                $festive = collect();
            }

            if ($festive->isEmpty()) {
                $festive = $baseQuery()
                    ->orderByDesc('is_featured')
                    ->orderByDesc('id')
                    ->take(8)
                    ->get();
            }

            // 3. New Season Arrivals
            $newArrivals = $baseQuery()
                ->orderByDesc('id')
                ->take(8)
                ->get();

            // 4. Boutique Best Sellers
            $bestSellers = $baseQuery()
                ->withCount('orderItems')
                ->orderByDesc('order_items_count')
                ->orderByDesc('id')
                ->take(8)
                ->get();

            if ($bestSellers->isEmpty()) {
                $bestSellers = $newArrivals;
            }

            return [
                'trending' => ProductListResource::collection($trending)->resolve(),
                'featured' => ProductListResource::collection($festive)->resolve(),
                'new_arrivals' => ProductListResource::collection($newArrivals)->resolve(),
                'best_sellers' => ProductListResource::collection($bestSellers)->resolve(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get paginated product catalog with dynamic multi-faceted filters.
     */
    public function index(ProductIndexRequest $request): JsonResponse
    {
        $query = Product::query()
            ->where('is_published', true)
            ->with([
                'category',
                'brand',
                'primaryImage',
                'images',
                'variants',
                'approvedReviews',
            ])
            ->withCount('orderItems');

        // 1. Parent Category filter (including child subcategories)
        if ($request->filled('category_slug') && $request->category_slug !== 'all' && $request->category_slug !== 'shop') {
            $category = Category::where('slug', $request->category_slug)->first();
            if ($category) {
                $categoryIds = $category->children()->pluck('id')->push($category->id);
                $query->whereIn('category_id', $categoryIds);
            }
        }

        // 2. Specific Subcategory filter (slugs or IDs)
        if ($request->filled('sub')) {
            $subs = is_array($request->sub) ? $request->sub : explode(',', $request->sub);
            $subs = array_filter(array_map('trim', $subs));

            if (! empty($subs)) {
                $subCategoryIds = Category::whereIn('slug', $subs)
                    ->orWhereIn('id', $subs)
                    ->pluck('id');

                $query->whereIn('category_id', $subCategoryIds);
            }
        }

        // 3. Price range filter
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');

        // Check if "under-499" tag is set
        if ($request->input('tag') === 'under-499' && empty($maxPrice)) {
            $maxPrice = 499;
        }

        if ($minPrice !== null && is_numeric($minPrice)) {
            $query->where(DB::raw('COALESCE(discount_price, base_selling_price)'), '>=', (float) $minPrice);
        }

        if ($maxPrice !== null && is_numeric($maxPrice)) {
            $query->where(DB::raw('COALESCE(discount_price, base_selling_price)'), '<=', (float) $maxPrice);
        }

        // 4. Variant Size filter
        if ($request->filled('sizes')) {
            $sizes = is_array($request->sizes) ? $request->sizes : explode(',', $request->sizes);
            $sizes = array_filter(array_map('trim', $sizes));

            if (! empty($sizes)) {
                $query->whereHas('variants', function ($vq) use ($sizes) {
                    $vq->whereIn('size', $sizes);
                });
            }
        }

        // 5. Variant Color filter
        if ($request->filled('colors')) {
            $colors = is_array($request->colors) ? $request->colors : explode(',', $request->colors);
            $colors = array_filter(array_map('trim', $colors));

            if (! empty($colors)) {
                $lowerColors = array_map('strtolower', $colors);
                $query->whereHas('variants', function ($vq) use ($lowerColors) {
                    $vq->whereIn(DB::raw('LOWER(color)'), $lowerColors);
                });
            }
        }

        // 6. Stock status filter
        if ($request->boolean('in_stock')) {
            $query->whereHas('variants', function ($vq) {
                $vq->where('stock_quantity', '>', 0);
            });
        }

        // 7. Featured / Hot badge filter
        if ($request->boolean('is_featured') || $request->input('tag') === 'featured') {
            $query->where('is_featured', true);
        }

        // 8. Keyword Search
        $searchTerm = $request->input('search') ?? $request->input('q');
        if ($searchTerm) {
            $query->where(function ($sq) use ($searchTerm) {
                $sq->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('base_sku', 'like', "%{$searchTerm}%")
                    ->orWhere('short_description', 'like', "%{$searchTerm}%");
            });
        }

        // 9. Minimum Rating filter
        if ($request->filled('min_rating')) {
            $minRating = (float) $request->input('min_rating');
            $query->whereHas('approvedReviews', function ($rq) use ($minRating) {
                $rq->having(DB::raw('AVG(rating)'), '>=', $minRating);
            });
        }

        // 10. Sorting
        $sort = $request->input('sort', 'featured');
        switch ($sort) {
            case 'price-low':
                $query->orderByRaw('COALESCE(discount_price, base_selling_price) ASC');
                break;
            case 'price-high':
                $query->orderByRaw('COALESCE(discount_price, base_selling_price) DESC');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'best-selling':
                $query->orderBy('order_items_count', 'desc');
                break;
            case 'rating':
                $query->withAvg('approvedReviews', 'rating')->orderBy('approved_reviews_avg_rating', 'desc');
                break;
            case 'featured':
            default:
                $query->orderBy('is_featured', 'desc')->orderBy('id', 'desc');
                break;
        }

        $perPage = min((int) $request->input('per_page', 12), 100);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProductListResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Get product details by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with([
                'category.parent',
                'brand',
                'primaryImage',
                'images',
                'variants' => function ($vq) {
                    $vq->where('status', 'active')->orderBy('id', 'asc');
                },
                'approvedReviews.user',
            ])
            ->withCount('orderItems')
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Load up to 4 related products from same category
        if ($product->category_id) {
            $related = Product::query()
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('is_published', true)
                ->with(['primaryImage', 'images', 'variants', 'approvedReviews'])
                ->limit(4)
                ->get();

            $product->setRelation('relatedProducts', $related);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductDetailResource($product),
        ]);
    }
}

