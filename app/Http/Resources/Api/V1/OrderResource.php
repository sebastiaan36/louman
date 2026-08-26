<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use App\Models\Product;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total = (float) $this->total;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => OrderStatus::label($this->status),
            'customer_number' => $this->whenLoaded('customer', fn () => $this->customer?->customer_number),
            'customer_external_id' => $this->whenLoaded('customer', fn () => $this->customer?->external_id),
            'delivery_address' => $this->whenLoaded(
                'deliveryAddress',
                fn () => $this->deliveryAddress ? new DeliveryAddressResource($this->deliveryAddress) : null,
            ),
            'notes' => $this->notes,
            'total_excluding_vat' => $this->total,
            'vat_rate' => Product::getVatRate(),
            'vat_amount' => number_format($total * Product::getVatRate(), 2, '.', ''),
            'total_including_vat' => number_format($total * (1 + Product::getVatRate()), 2, '.', ''),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
