<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    /**
     * Orders placed with this coupon.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Check if coupon is currently valid.
     */
    public function isValid(float $orderAmount = 0.0): bool
    {
        if ($this->status !== 'active') return false;

        $now = now();
        if ($this->start_date && $now->lt($this->start_date)) return false;
        if ($this->end_date && $now->gt($this->end_date)) return false;

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) return false;

        if ($this->min_order_amount > 0 && $orderAmount < $this->min_order_amount) return false;

        return true;
    }
}

