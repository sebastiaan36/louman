<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProductPrice extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'product_id',
        'custom_price',
        'custom_price_per_kg',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'custom_price' => 'decimal:2',
            'custom_price_per_kg' => 'decimal:2',
        ];
    }

    /**
     * Get the customer that owns the custom price.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the product the custom price applies to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
