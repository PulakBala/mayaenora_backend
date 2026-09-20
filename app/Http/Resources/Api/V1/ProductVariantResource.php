<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
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
            'sku' => $this->variant_sku,
            'variant_name' => $this->variant_name,
            'size' => $this->size,
            'color' => $this->color,
            'material' => $this->material,
            'regular_price' => (float) $this->regular_price,
            'sale_price' => $this->sale_price !== null ? (float) $this->sale_price : null,
            'effective_price' => (float) $this->effective_price,
            'image' => $this->normalizeImageUrl($this->image),
            'stock_quantity' => (int) $this->stock_quantity,
            'in_stock' => $this->stock_quantity > 0,
            'status' => $this->status,
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

        if (str_starts_with($url, 'products/') || str_starts_with($url, 'gallery/') || str_starts_with($url, 'categories/') || str_starts_with($url, 'banners/') || str_starts_with($url, 'uploads/')) {
            return '/storage/' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        return '/storage/' . $url;
    }
}

