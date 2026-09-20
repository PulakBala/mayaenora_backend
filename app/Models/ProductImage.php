<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'image_url',
        'image_path',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Map image_path to image_url so both work interchangeably.
     */
    public function getImagePathAttribute(): ?string
    {
        return $this->attributes['image_url'] ?? null;
    }

    public function setImagePathAttribute(?string $value): void
    {
        $this->attributes['image_url'] = $value;
    }

    /**
     * Product associated with this image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

