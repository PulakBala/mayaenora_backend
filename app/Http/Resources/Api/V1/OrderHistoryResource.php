<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderHistoryResource extends JsonResource
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
            'order_number' => $this->order_number,
            'created_at' => $this->created_at?->toIso8601String(),
            'order_status' => $this->order_status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'grand_total' => (float) $this->grand_total,
            'delivery_charge' => (float) $this->delivery_charge,
            'courier_partner' => $this->courier_partner,
            'courier_tracking_code' => $this->courier_tracking_code,
            'courier_tracking_url' => $this->getTrackingUrl($this->courier_partner, $this->courier_tracking_code),
            'courier_consignment_id' => $this->courier_consignment_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'shipping_district' => $this->shipping_district,
            'shipping_address' => $this->shipping_address,
            'customer_notes' => $this->customer_notes,
            'items' => $this->relationLoaded('items')
                ? $this->items->map(function ($item) {
                    $product = $item->product;
                    $variant = $item->variant;

                    $rawImg = $product?->primaryImage?->image_url
                        ?? $product?->primaryImage?->image_path
                        ?? $product?->images?->first()?->image_url
                        ?? $product?->images?->first()?->image_path
                        ?? $variant?->image
                        ?? '/images/products/placeholder.jpg';

                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_title' => $item->product_name ?? $product?->title ?? 'Item',
                        'variant_attributes' => $item->variant_details,
                        'thumbnail_image' => $this->normalizeImageUrl($rawImg),
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_sale_price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                })
                : [],
        ];
    }

    /**
     * Compute public courier tracking portal URL based on partner & code.
     */
    protected function getTrackingUrl(?string $partner, ?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        return match (strtolower($partner ?? '')) {
            'steadfast' => "https://steadfast.com.bd/t/{$code}",
            'pathao' => "https://merchant.pathao.com/tracking?consignment_id={$code}",
            'redx' => "https://redx.com.bd/track-order?trackingId={$code}",
            default => null,
        };
    }

    /**
     * Normalize image URLs to ensure valid full or public storage paths.
     */
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

        if (
            str_starts_with($url, 'products/') ||
            str_starts_with($url, 'gallery/') ||
            str_starts_with($url, 'categories/') ||
            str_starts_with($url, 'banners/') ||
            str_starts_with($url, 'uploads/')
        ) {
            return '/storage/' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        return '/storage/' . $url;
    }
}

