<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'name',
        'api_key',
        'secret_key',
        'client_id',
        'base_url',
        'is_active',
        'sandbox_mode',
        'webhook_secret',
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
            'api_key' => 'encrypted',
            'secret_key' => 'encrypted',
            'client_id' => 'encrypted',
            'is_active' => 'boolean',
            'sandbox_mode' => 'boolean',
            'additional_settings' => 'array',
        ];
    }

    /**
     * Scope a query to only include active couriers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

