<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled',
        'commission_type',
        'commission_value',
        'minimum_order_value',
        'minimum_payout_threshold',
        'reward_frequency',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'commission_value' => 'decimal:2',
            'minimum_order_value' => 'decimal:2',
            'minimum_payout_threshold' => 'decimal:2',
        ];
    }

    /**
     * Retrieve the singleton referral configuration record.
     */
    public static function getSettings(): self
    {
        return static::firstOrCreate([], [
            'is_enabled' => true,
            'commission_type' => 'percentage',
            'commission_value' => 5.00,
            'minimum_order_value' => 500.00,
            'minimum_payout_threshold' => 500.00,
            'reward_frequency' => 'first_order_only',
        ]);
    }
}

