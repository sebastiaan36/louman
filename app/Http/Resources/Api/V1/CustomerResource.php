<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Customer
 */
class CustomerResource extends JsonResource
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
            'customer_number' => $this->customer_number,
            'external_id' => $this->external_id,
            'company_name' => $this->company_name,
            'contact_person' => $this->contact_person,
            'phone_number' => $this->phone_number,
            'mobile_number' => $this->mobile_number,
            'street_name' => $this->street_name,
            'house_number' => $this->house_number,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'kvk_number' => $this->kvk_number,
            'vat_number' => $this->vat_number,
            'bank_account' => $this->bank_account,
            'packing_slip_email' => $this->packing_slip_email,
            'invoice_email' => $this->invoice_email,
            'customer_category' => $this->customer_category,
            'discount_percentage' => $this->discount_percentage,
            'delivery_day' => $this->delivery_day,
            'route_order' => $this->route_order,
            'packaging_notes' => $this->packaging_notes,
            'is_approved' => $this->approved_at !== null,
            'is_active' => $this->deactivated_at === null,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'deactivated_at' => $this->deactivated_at?->toIso8601String(),
            'terms_accepted_at' => $this->terms_accepted_at?->toIso8601String(),
            'account' => $this->whenLoaded('user', fn () => [
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'email_verified_at' => $this->user?->email_verified_at?->toIso8601String(),
            ]),
            'delivery_addresses' => DeliveryAddressResource::collection($this->whenLoaded('deliveryAddresses')),
            'prices' => CustomerPriceResource::collection($this->whenLoaded('customProductPrices')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'synced_at' => $this->synced_at?->toIso8601String(),
        ];
    }
}
