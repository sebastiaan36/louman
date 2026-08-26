<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DeliveryAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliveryAddress
 */
class DeliveryAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'street_name' => $this->street_name,
            'house_number' => $this->house_number,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'notes' => $this->notes,
            'is_default' => (bool) $this->is_default,
        ];
    }
}
