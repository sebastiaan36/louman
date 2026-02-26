<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'contact_person',
        'phone_number',
        'street_name',
        'house_number',
        'postal_code',
        'city',
        'kvk_number',
        'bank_account',
        'vat_number',
        'packing_slip_email',
        'approved_at',
        'approved_by',
        'customer_category',
        'discount_percentage',
        'delivery_day',
        'route_order',
        'show_on_map',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'show_on_map' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the customer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin user that approved the customer.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only include approved customers.
     */
    public function scopeApproved(Builder $query): void
    {
        $query->whereNotNull('approved_at');
    }

    /**
     * Determine if the customer is approved.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Get the delivery addresses for the customer.
     */
    public function deliveryAddresses(): HasMany
    {
        return $this->hasMany(DeliveryAddress::class);
    }

    /**
     * Get the favorite products for the customer.
     */
    public function favoriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_favorites')->withTimestamps();
    }

    /**
     * Get the cart items for the customer.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the orders for the customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the human-readable category label.
     */
    public function getCategoryLabel(): ?string
    {
        return match ($this->customer_category) {
            'groothandel' => 'Groothandel',
            'broodjeszaak' => 'Broodjeszaak',
            'horeca' => 'Horeca',
            default => null,
        };
    }
}
