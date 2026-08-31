<?php

namespace App\Http\Requests\Api\V1;

use App\Support\DeliveryDay;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Partial update of a customer record. Only the fields present in the payload
 * are written, so the external package can push back just the customer number
 * and external id after it has created the account on its side.
 *
 * Registration, approval and the login account stay with the portal and cannot
 * be changed through this endpoint.
 */
class UpdateCustomerRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'customer_number' => [
                'sometimes', 'nullable', 'string', 'max:50',
                Rule::unique('customers', 'customer_number')->ignore($customerId),
            ],
            'external_id' => [
                'sometimes', 'nullable', 'string', 'max:100',
                Rule::unique('customers', 'external_id')->ignore($customerId),
            ],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'mobile_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'street_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'house_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'kvk_number' => [
                'sometimes', 'nullable', 'string', 'max:20',
                Rule::unique('customers', 'kvk_number')->ignore($customerId),
            ],
            'vat_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'bank_account' => ['sometimes', 'nullable', 'string', 'max:34'],
            'packing_slip_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'invoice_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'customer_category' => ['sometimes', 'nullable', 'in:groothandel,broodjeszaak,horeca'],
            'discount_percentage' => ['sometimes', 'nullable', 'in:1,2,3,4,5'],
            'delivery_day' => ['sometimes', 'nullable', DeliveryDay::rule()],
            'route_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'packaging_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_number.unique' => 'Dit klantnummer is al aan een andere klant gekoppeld.',
            'external_id.unique' => 'Deze externe id is al aan een andere klant gekoppeld.',
            'kvk_number.unique' => 'Dit KvK-nummer is al aan een andere klant gekoppeld.',
            'customer_category.in' => 'Ongeldige klantcategorie. Kies groothandel, broodjeszaak of horeca.',
            'discount_percentage.in' => 'Het kortingspercentage moet 1, 2, 3, 4 of 5 zijn.',
            'delivery_day.in' => 'Ongeldige bezorgdag.',
        ];
    }
}
