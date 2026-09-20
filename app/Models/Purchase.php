<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'reference_no',
        'purchase_date',
        'total_cost',
        'paid_amount',
        'due_amount',
        'payment_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total_cost' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
        ];
    }

    /**
     * Supplier for this purchase.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Items in this purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}

