<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_sku',
        'size',
        'color',
        'material',
        'variant_name',
        'cost_price',
        'regular_price',
        'sale_price',
        'image',
        'stock_quantity',
        'low_stock_threshold',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'regular_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    /**
     * Parent product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Inventory audit logs for this variant.
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Purchase line items for this variant.
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Order items containing this variant.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Effective selling price (sale price if available, else regular).
     */
    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->regular_price);
    }

    /**
     * Check if variant is in stock.
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }
}

