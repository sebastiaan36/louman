<?php

namespace App\Http\Requests\Customer;

use App\Concerns\CustomerValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class DeliveryAddressRequest extends FormRequest
{
    use CustomerValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isCustomer();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'street_name' => $this->streetNameRules(),
            'house_number' => $this->houseNumberRules(),
            'postal_code' => $this->postalCodeRules(),
            'city' => $this->cityRules(),
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'naam',
            'street_name' => 'straatnaam',
            'house_number' => 'huisnummer',
            'postal_code' => 'postcode',
            'city' => 'plaats',
            'notes' => 'notities',
            'is_default' => 'standaard adres',
        ];
    }
}
