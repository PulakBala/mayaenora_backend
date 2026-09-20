<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $primaryImg = $this->primaryImage?->image_url
            ?? $this->primaryImage?->image_path
            ?? $this->images->first()?->image_url
            ?? $this->images->first()?->image_path
            ?? $this->variants->first()?->image
            ?? '/images/products/placeholder.jpg';

        $regularPrice = (float) $this->base_selling_price;
        $discountPrice = $this->discount_price !== null ? (float) $this->discount_price : null;
        $effectivePrice = $discountPrice ?? $regularPrice;

        // Discount badge calculation
        $badge = null;
        if ($discountPrice !== null && $discountPrice < $regularPrice) {
            $percentage = round((($regularPrice - $discountPrice) / $regularPrice) * 100);
            $badge = [
                'text' => "-{$percentage}%",
                'type' => 'discount',
            ];
        } elseif ($this->is_featured) {
            $badge = [
                'text' => 'Featured',
                'type' => 'hot',
            ];
        }

        // Reviews calculation
        $approvedReviews = $this->relationLoaded('approvedReviews') ? $this->approvedReviews : collect();
        $avgRating = $approvedReviews->count() > 0 ? round($approvedReviews->avg('rating'), 1) : 4.8;
        $reviewsCount = $approvedReviews->count();

        // Total stock across variants
        $variants = $this->relationLoaded('variants') ? $this->variants : collect();
        $totalStock = $variants->sum('stock_quantity');
        $inStock = $totalStock > 0;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'sku' => $this->base_sku,
            'badge' => $badge,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'image' => $this->normalizeImageUrl($primaryImg),
            'gallery' => $this->relationLoaded('images') && $this->images->count() > 0
                ? $this->images->map(fn ($img) => $this->normalizeImageUrl($img->image_url ?? $img->image_path))->filter()->values()->all()
                : [$this->normalizeImageUrl($primaryImg)],
            'price' => $effectivePrice,
            'original_price' => $discountPrice !== null ? $regularPrice : null,
            'discount_type' => $this->discount_type,
            'rating' => (float) $avgRating,
            'reviews_count' => $reviewsCount,
            'sales_count' => (int) ($this->order_items_count ?? 120),
            'total_stock' => (int) $totalStock,
            'in_stock' => $inStock,
            'is_featured' => (bool) $this->is_featured,
            'allow_reviews' => (bool) $this->allow_reviews,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'custom_fields' => $this->custom_fields,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'parent' => $this->category->parent ? [
                    'id' => $this->category->parent->id,
                    'name' => $this->category->parent->name,
                    'slug' => $this->category->parent->slug,
                ] : null,
            ] : null,
            'brand' => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'logo' => $this->brand->logo,
            ] : null,
            'variants' => ProductVariantResource::collection($variants),
            'reviews' => ReviewResource::collection($approvedReviews),
            'related_products' => ProductListResource::collection($this->whenLoaded('relatedProducts')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    protected function normalizeImageUrl(?string $url): string
    {
        if (! $url) {
            return '/images/products/placeholder.jpg';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '/storage/') || str_starts_with($url, '/images/')) {
            return $url;
        }

        if (str_starts_with($url, 'storage/')) {
            return '/' . $url;
        }

        if (str_starts_with($url, 'products/') || str_starts_with($url, 'gallery/') || str_starts_with($url, 'categories/') || str_starts_with($url, 'banners/') || str_starts_with($url, 'uploads/')) {
            return '/storage/' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        return '/storage/' . $url;
    }
}

