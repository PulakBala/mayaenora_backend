<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'product_variant_id',
        'transaction_type',
        'quantity_change',
        'remaining_stock',
        'reference_id',
        'remarks',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'remaining_stock' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Product variant being logged.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * User who logged the inventory change.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

