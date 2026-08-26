<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CustomerProductPrice
 */
class CustomerPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'article_number' => $this->whenLoaded('product', fn () => $this->product?->article_number),
            'custom_price' => $this->custom_price,
            'custom_price_per_kg' => $this->custom_price_per_kg,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
