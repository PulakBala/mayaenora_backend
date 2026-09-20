<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
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
        $avgRating = $this->relationLoaded('approvedReviews') && $this->approvedReviews->count() > 0
            ? round($this->approvedReviews->avg('rating'), 1)
            : 4.8;
        $reviewsCount = $this->relationLoaded('approvedReviews')
            ? $this->approvedReviews->count()
            : ($this->reviews_count ?? 0);

        // Variants attributes
        $colors = $this->relationLoaded('variants')
            ? $this->variants->pluck('color')->filter()->unique()->values()->all()
            : [];
        $sizes = $this->relationLoaded('variants')
            ? $this->variants->pluck('size')->filter()->unique()->values()->all()
            : [];
        $inStock = $this->relationLoaded('variants')
            ? $this->variants->contains(fn ($v) => $v->stock_quantity > 0)
            : true;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'sku' => $this->base_sku,
            'badge' => $badge,
            'image' => $this->normalizeImageUrl($primaryImg),
            'gallery' => $this->relationLoaded('images') && $this->images->count() > 0
                ? $this->images->map(fn ($img) => $this->normalizeImageUrl($img->image_url ?? $img->image_path))->filter()->values()->all()
                : [$this->normalizeImageUrl($primaryImg)],
            'price' => $effectivePrice,
            'original_price' => $discountPrice !== null ? $regularPrice : null,
            'rating' => (float) $avgRating,
            'reviews_count' => (int) $reviewsCount,
            'sales_count' => (int) ($this->order_items_count ?? 120),
            'in_stock' => $inStock,
            'is_featured' => (bool) $this->is_featured,
            'has_add_to_cart' => true,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'brand' => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null,
            'colors' => $colors,
            'sizes' => $sizes,
            'default_variant_id' => $this->relationLoaded('variants') ? $this->variants->first()?->id : null,
            'variants' => $this->relationLoaded('variants') ? ProductVariantResource::collection($this->variants) : [],
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

