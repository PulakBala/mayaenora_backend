<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnsRefund extends Model
{
    use HasFactory;

    protected $table = 'returns_refunds';

    protected $fillable = [
        'order_id',
        'user_id',
        'reason',
        'amount_requested',
        'amount_refunded',
        'status',
        'proof_images',
    ];

    protected function casts(): array
    {
        return [
            'amount_requested' => 'decimal:2',
            'amount_refunded' => 'decimal:2',
            'proof_images' => 'array',
        ];
    }

    /**
     * Associated order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Customer requesting the refund.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

