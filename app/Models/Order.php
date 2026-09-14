<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'delivery_address_id',
        'total',
        'status',
        'notes',
    ];

    /**
     * Bootstrap the model: a new order answers the question the delivery-route
     * markers were asking, so "terugbellen" and "niet deze week" go off.
     */
    protected static function booted(): void
    {
        static::created(function (Order $order): void {
            $customer = $order->customer;

            if ($customer !== null && $customer->route_flag !== null) {
                $customer->setRouteFlag(null);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
        ];
    }

    /**
     * Get the customer that owns the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the delivery address for the order.
     */
    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(DeliveryAddress::class);
    }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
