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
        'customer_number',
        'contact_person',
        'phone_number',
        'mobile_number',
        'street_name',
        'house_number',
        'postal_code',
        'city',
        'kvk_number',
        'bank_account',
        'vat_number',
        'packing_slip_email',
        'customer_category',
        'discount_percentage',
        'delivery_day',
        'route_order',
        'show_on_map',
        'terms_accepted_at',
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
            'deactivated_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
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
     * Scope a query to only include active (not deactivated) customers.
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('deactivated_at');
    }

    /**
     * Determine if the customer is active (not deactivated).
     */
    public function isActive(): bool
    {
        return $this->deactivated_at === null;
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
     * Get the customer's custom product prices (sparse: only deviating prices).
     */
    public function customProductPrices(): HasMany
    {
        return $this->hasMany(CustomerProductPrice::class);
    }

    /**
     * Get the custom price for a product, or null if none is set.
     * Reads from the loaded relation when available to avoid N+1 queries.
     */
    public function customPriceFor(int $productId): ?string
    {
        if ($this->relationLoaded('customProductPrices')) {
            $row = $this->customProductPrices->firstWhere('product_id', $productId);

            return $row?->custom_price;
        }

        return $this->customProductPrices()->where('product_id', $productId)->value('custom_price');
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
