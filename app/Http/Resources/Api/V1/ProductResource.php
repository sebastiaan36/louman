<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'article_number' => $this->article_number,
            'external_id' => $this->external_id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'price_per_kg' => $this->price_per_kg,
            'suggested_retail_price' => $this->suggested_retail_price,
            'weight' => $this->weight,
            'ingredients' => $this->ingredients ?? [],
            'allergens' => $this->allergens ?? [],
            'nutrition_facts' => $this->nutrition_facts,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'subcategory' => $this->whenLoaded('subcategory', fn () => $this->subcategory?->name),
            'photo_url' => $this->photo_url,
            'in_stock' => $this->in_stock,
            'is_active' => $this->is_active,
            'is_private_label' => $this->is_private_label,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'synced_at' => $this->synced_at?->toIso8601String(),
        ];
    }
}
