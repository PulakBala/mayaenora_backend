<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'tag',
        'subtitle',
        'image',
        'desktop_image',
        'mobile_image',
        'link_url',
        'target_url',
        'button_text',
        'placement',
        'position',
        'sort_order',
        'is_active',
        'status',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    /**
     * Scope active banners that are currently within scheduled dates.
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where(function ($q) {
            $q->where('is_active', true)
                ->orWhere('status', 'active');
        })->where(function ($q) use ($now) {
            $q->whereNull('start_date')
                ->orWhere('start_date', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $now);
        });
    }

    /**
     * Scope by placement (e.g. hero_slider, promo_strip, sidebar).
     */
    public function scopePlacement(Builder $query, string $placement): Builder
    {
        return $query->where(function ($q) use ($placement) {
            $q->where('placement', $placement);

            if ($placement === 'hero_slider') {
                $q->orWhere('position', 'top_slider');
            } elseif ($placement === 'sidebar') {
                $q->orWhere('position', 'sidebar');
            } elseif ($placement === 'promo_strip') {
                $q->orWhere('position', 'home_middle');
            }
        });
    }

    /**
     * Ensure synchronized attributes before saving.
     */
    protected static function booted(): void
    {
        static::saving(function (Banner $banner) {
            // Keep image and desktop_image in sync
            if (! empty($banner->image) && empty($banner->desktop_image)) {
                $banner->desktop_image = $banner->image;
            } elseif (empty($banner->image) && ! empty($banner->desktop_image)) {
                $banner->image = $banner->desktop_image;
            }

            // Keep link_url and target_url in sync
            if (! empty($banner->link_url) && empty($banner->target_url)) {
                $banner->target_url = $banner->link_url;
            } elseif (empty($banner->link_url) && ! empty($banner->target_url)) {
                $banner->link_url = $banner->target_url;
            }

            // Keep placement and position in sync
            if (! empty($banner->placement) && empty($banner->position)) {
                $banner->position = $banner->placement === 'hero_slider' ? 'top_slider' : $banner->placement;
            } elseif (empty($banner->placement) && ! empty($banner->position)) {
                $banner->placement = $banner->position === 'top_slider' ? 'hero_slider' : $banner->position;
            }

            // Keep is_active and status in sync
            if ($banner->is_active !== null) {
                $banner->status = $banner->is_active ? 'active' : 'inactive';
            } elseif (! empty($banner->status)) {
                $banner->is_active = ($banner->status === 'active');
            }
        });
    }
}
