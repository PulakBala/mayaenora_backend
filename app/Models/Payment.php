<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'transaction_id',
        'gateway_name',
        'amount',
        'fee_deducted',
        'status',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee_deducted' => 'decimal:2',
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Associated order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

