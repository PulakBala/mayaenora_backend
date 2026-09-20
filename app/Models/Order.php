<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'coupon_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_district',
        'shipping_address',
        'subtotal_amount',
        'discount_amount',
        'delivery_charge',
        'grand_total',
        'total_product_cost',
        'packaging_cost',
        'actual_courier_cost',
        'payment_gateway_fee',
        'net_profit',
        'payment_method',
        'payment_status',
        'order_status',
        'courier_partner',
        'courier_tracking_code',
        'courier_consignment_id',
        'customer_notes',
        'admin_notes',
        'cancelled_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'total_product_cost' => 'decimal:2',
            'packaging_cost' => 'decimal:2',
            'actual_courier_cost' => 'decimal:2',
            'payment_gateway_fee' => 'decimal:2',
            'net_profit' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /**
     * Customer who placed the order (null for pure guest).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Applied coupon if any.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Line items in this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Payments / transactions for this order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Order status history records.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Return and refund requests for this order.
     */
    public function returns(): HasMany
    {
        return $this->hasMany(ReturnsRefund::class);
    }
}

