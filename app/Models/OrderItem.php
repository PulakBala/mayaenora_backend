<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_details',
        'quantity',
        'unit_cost_price',
        'unit_sale_price',
        'subtotal',
        'item_net_profit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost_price' => 'decimal:2',
            'unit_sale_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'item_net_profit' => 'decimal:2',
        ];
    }

    /**
     * Parent order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Associated product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Specific product variant.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}

