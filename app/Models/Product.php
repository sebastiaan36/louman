<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'title',
        'photo',
        'price_groothandel',
        'price_broodjeszaak',
        'price_horeca',
        'description',
        'ingredients',
        'allergens',
        'nutrition_facts',
        'weight',
        'article_number',
        'in_stock',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_groothandel' => 'decimal:2',
            'price_broodjeszaak' => 'decimal:2',
            'price_horeca' => 'decimal:2',
            'ingredients' => 'array',
            'allergens' => 'array',
            'nutrition_facts' => 'array',
            'in_stock' => 'boolean',
            'is_active' => 'boolean',
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
     * Get the customers that favorited this product.
     */
    public function favoritedByCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'product_favorites')->withTimestamps();
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
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
     * Get the price for a specific customer based on their category.
     */
    public function getPriceForCustomer(Customer $customer): string
    {
        $basePrice = match ($customer->customer_category) {
            'groothandel' => $this->price_groothandel,
            'broodjeszaak' => $this->price_broodjeszaak,
            'horeca' => $this->price_horeca,
            default => $this->price_groothandel,
        };

        // Apply discount if customer has one
        if ($customer->discount_percentage) {
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
