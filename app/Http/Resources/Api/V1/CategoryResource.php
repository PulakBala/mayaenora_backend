<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon_image' => $this->normalizeImageUrl($this->image ?: "/images/categories/{$this->slug}.svg"),
            'banner_image' => $this->banner_image ? $this->normalizeImageUrl($this->banner_image) : null,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'is_featured' => (bool) $this->is_featured,
            'sort_order' => (int) $this->sort_order,
            'products_count' => $this->whenCounted('products', (int) $this->products_count, 0),
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'subcategories' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }

    protected function normalizeImageUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
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

        if (str_starts_with($url, 'categories/') || str_starts_with($url, 'banners/') || str_starts_with($url, 'uploads/')) {
            return '/storage/' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        return '/storage/' . $url;
    }
}

