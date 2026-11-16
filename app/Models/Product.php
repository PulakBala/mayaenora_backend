<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'brand_id',
        'title',
        'slug',
        'base_sku',
        'short_description',
        'description',
        'base_cost_price',
        'base_selling_price',
        'discount_price',
        'discount_type',
        'discount_start',
        'discount_end',
        'is_featured',
        'is_published',
        'allow_reviews',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'base_cost_price' => 'decimal:2',
            'base_selling_price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'discount_start' => 'datetime',
            'discount_end' => 'datetime',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'allow_reviews' => 'boolean',
            'custom_fields' => 'array',
        ];
    }

    /**
     * Category of the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Brand of the product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Product gallery images.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Primary hero image.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Product variants (sizes, colors, etc.).
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Customer reviews.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Approved reviews.
     */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    /**
     * Wishlist entries for this product.
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Orders containing this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Purchases containing this product.
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Auto-invalidate home sections cache when products change.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('api_home_sections_v1');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('api_home_sections_v1');
        });
    }
}

