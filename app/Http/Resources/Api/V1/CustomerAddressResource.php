<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
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
            'type' => $this->type ?? 'shipping',
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'district' => $this->district,
            'city_zone' => $this->city_zone,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

