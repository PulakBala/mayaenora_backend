<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'phone',
        'phone_verified_at',
        'email_verified_at',
        'password',
        'two_factor_secret',
        'two_factor_enabled',
        'status',
        'avatar',
        'metadata',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Role of the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if user has specific role slug.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->role?->slug === $roleSlug;
    }

    /**
     * Determine if user can access Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role?->slug, ['admin', 'super-admin', 'manager', 'operations-manager', 'staff', 'support-staff']);
    }

    /**
     * Saved customer addresses.
     */
    public function customerAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Default shipping address.
     */
    public function defaultShippingAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }

    /**
     * Orders placed by the user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Wishlist records.
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Favorited products through wishlist.
     */
    public function wishlistProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withTimestamps();
    }

    /**
     * Reviews submitted by user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Return and refund requests.
     */
    public function returnsRefunds(): HasMany
    {
        return $this->hasMany(ReturnsRefund::class);
    }

    /**
     * Audit logs created by user action.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Expenses recorded by this staff user.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    /**
     * Inventory adjustments made by this user.
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class, 'created_by');
    }
}
