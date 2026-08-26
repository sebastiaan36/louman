<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\OrderItem
 */
class OrderItemResource extends JsonResource
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
            'title' => $this->whenLoaded('product', fn () => $this->product?->title),
            'quantity' => $this->quantity,
            'price' => $this->price,
            'line_total' => number_format((float) $this->price * $this->quantity, 2, '.', ''),
        ];
    }
}
