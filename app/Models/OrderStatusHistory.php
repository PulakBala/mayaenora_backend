<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'status',
        'comment',
        'notified_customer',
        'updated_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_customer' => 'boolean',
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

    /**
     * User/Staff who updated the status.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

