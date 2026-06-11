<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'subcategory_id',
        'title',
        'photo',
        'price',
        'price_per_kg',
        'description',
        'ingredients',
        'allergens',
        'nutrition_facts',
        'weight',
        'article_number',
        'in_stock',
        'is_active',
        'is_private_label',
        'suggested_retail_price',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_per_kg' => 'decimal:2',
            'suggested_retail_price' => 'decimal:2',
            'ingredients' => 'array',
            'allergens' => 'array',
            'nutrition_facts' => 'array',
            'in_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_private_label' => 'boolean',
        ];
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the subcategory that owns the product.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    /**
     * Get the order items for this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the customers a private-label product is visible to.
     */
    public function visibleToCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_product_visibility')->withTimestamps();
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to only products visible to the given customer:
     * all standard products plus the private-label products linked to them.
     */
    public function scopeVisibleTo(Builder $query, Customer $customer): void
    {
        $query->where(function (Builder $q) use ($customer) {
            $q->where('is_private_label', false)
                ->orWhereHas('visibleToCustomers', function (Builder $c) use ($customer) {
                    $c->whereKey($customer->id);
                });
        });
    }

    /**
     * Determine if this product is visible to the given customer.
     */
    public function isVisibleTo(Customer $customer): bool
    {
        if (! $this->is_private_label) {
            return true;
        }

        return $this->visibleToCustomers()->whereKey($customer->id)->exists();
    }

    /**
     * Scope a query to only include products in stock.
     */
    public function scopeInStock(Builder $query): void
    {
        $query->where('in_stock', true);
    }

    /**
     * Get the product's photo URL.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo ? asset('storage/'.$this->photo) : null,
        );
    }

    /**
     * Get the product's thumbnail URL.
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo ? asset('storage/products/thumbs/'.basename($this->photo)) : null,
        );
    }

    /**
     * Determine if the product is in stock.
     */
    public function isInStock(): bool
    {
        return $this->in_stock;
    }

    /**
     * Get the price for a specific customer.
     *
     * A custom price set for this (customer, product) wins and is used as-is,
     * without the discount percentage. Otherwise the standard price applies,
     * with the customer's discount if any — except for private-label products,
     * which are always sold at their standard price.
     */
    public function getPriceForCustomer(Customer $customer): string
    {
        $customPrice = $this->id ? $customer->customPriceFor($this->id) : null;

        if ($customPrice !== null) {
            return number_format((float) $customPrice, 2, '.', '');
        }

        $basePrice = $this->price;

        if (! $this->is_private_label && $customer->discount_percentage) {
            $discount = (float) $customer->discount_percentage;
            $basePrice = $basePrice * (1 - ($discount / 100));
        }

        return number_format($basePrice, 2, '.', '');
    }

    /**
     * Get the VAT rate (9%).
     */
    public static function getVatRate(): float
    {
        return 0.09;
    }

    /**
     * Calculate VAT amount for a given price (ex. VAT).
     */
    public static function calculateVat(float $priceExVat): float
    {
        return $priceExVat * self::getVatRate();
    }

    /**
     * Calculate price including VAT.
     */
    public static function calculatePriceInclVat(float $priceExVat): float
    {
        return $priceExVat * (1 + self::getVatRate());
    }
}
