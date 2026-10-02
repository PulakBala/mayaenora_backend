<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FooterSection extends Model
{
    use HasFactory;

    protected $table = 'footer_sections';

    protected $fillable = [
        'title',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function links(): HasMany
    {
        return $this->hasMany(FooterLink::class, 'footer_section_id')->orderBy('sort_order', 'asc');
    }

    public function activeLinks(): HasMany
    {
        return $this->hasMany(FooterLink::class, 'footer_section_id')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc');
    }
}
