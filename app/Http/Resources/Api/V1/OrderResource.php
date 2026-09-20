<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'customer' => [
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
                'email' => $this->customer_email,
                'user_id' => $this->user_id,
            ],
            'shipping' => [
                'district' => $this->shipping_district,
                'address' => $this->shipping_address,
                'delivery_charge' => (float) $this->delivery_charge,
            ],
            'pricing' => [
                'subtotal' => (float) $this->subtotal_amount,
                'discount' => (float) $this->discount_amount,
                'delivery_charge' => (float) $this->delivery_charge,
                'grand_total' => (float) $this->grand_total,
            ],
            'status' => [
                'order_status' => $this->order_status,
                'payment_status' => $this->payment_status,
                'payment_method' => $this->payment_method,
            ],
            'items' => $this->relationLoaded('items')
                ? $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_name' => $item->product_name,
                        'variant_details' => $item->variant_details,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_sale_price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                })
                : [],
            'customer_notes' => $this->customer_notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

