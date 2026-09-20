<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'rating' => (int) $this->rating,
            'review_title' => $this->review_title,
            'review_text' => $this->review_text,
            'images' => $this->images,
            'is_verified_purchase' => (bool) $this->is_verified_purchase,
            'reviewer_name' => $this->user?->name ?? 'Verified Buyer',
            'reviewer_avatar' => $this->user?->avatar,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

