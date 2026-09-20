<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway_key',
        'name',
        'store_id',
        'store_passwd',
        'base_url',
        'currency',
        'fee_percentage',
        'is_active',
        'sandbox_mode',
        'additional_settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'store_id' => 'encrypted',
            'store_passwd' => 'encrypted',
            'fee_percentage' => 'decimal:2',
            'is_active' => 'boolean',
            'sandbox_mode' => 'boolean',
            'additional_settings' => 'array',
        ];
    }

    /**
     * Scope a query to only include active gateways.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

