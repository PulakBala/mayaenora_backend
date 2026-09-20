<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'identifier',
        'otp_code',
        'purpose',
        'expires_at',
        'is_used',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'is_used' => 'boolean',
        ];
    }

    /**
     * Check if OTP is still valid.
     */
    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }
}

