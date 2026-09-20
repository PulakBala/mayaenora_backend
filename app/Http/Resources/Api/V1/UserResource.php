<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'role' => $this->role?->slug ?? 'customer',
            'role_name' => $this->role?->name ?? 'Customer',
            'default_address' => new CustomerAddressResource($this->whenLoaded('defaultShippingAddress', $this->defaultShippingAddress)),
            'addresses' => CustomerAddressResource::collection($this->whenLoaded('customerAddresses')),
            'orders_count' => $this->whenCounted('orders', (int) $this->orders_count, function () {
                return $this->orders()->count();
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

