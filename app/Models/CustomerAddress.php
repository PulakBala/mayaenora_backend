<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'recipient_name',
        'recipient_phone',
        'district',
        'city_zone',
        'address',
        'postal_code',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * User owning this address.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

