<?php

namespace App\Http\Requests\Settings;

use App\Concerns\CustomerValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use CustomerValidationRules, ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->profileRules($this->user()->id);

        // If user is a customer, add customer validation rules
        if ($this->user()->isCustomer() && $this->user()->customer) {
            $customer = $this->user()->customer;
            $rules = array_merge($rules, [
                'company_name' => $this->companyNameRules(),
                'kvk_number' => $this->kvkNumberUpdateRules($customer->id),
                'vat_number' => $this->vatNumberRules(),
                'contact_person' => $this->contactPersonRules(),
                'phone_number' => $this->phoneNumberRules(),
                'street_name' => $this->streetNameRules(),
                'house_number' => $this->houseNumberRules(),
                'postal_code' => $this->postalCodeRules(),
                'city' => $this->cityRules(),
                'bank_account' => $this->bankAccountRules(),
            ]);
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'name' => 'naam',
            'email' => 'e-mailadres',
        ];

        if ($this->user()->isCustomer()) {
            $attributes = array_merge($attributes, [
                'company_name' => 'bedrijfsnaam',
                'kvk_number' => 'KvK nummer',
                'vat_number' => 'BTW nummer',
                'contact_person' => 'contactpersoon',
                'phone_number' => 'telefoonnummer',
                'street_name' => 'straatnaam',
                'house_number' => 'huisnummer',
                'postal_code' => 'postcode',
                'city' => 'plaats',
                'bank_account' => 'rekeningnummer',
            ]);
        }

        return $attributes;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.email' => 'Vul een geldig e-mailadres in.',
            'email.unique' => 'Dit e-mailadres is al in gebruik.',
            'kvk_number.unique' => 'Dit KvK nummer is al geregistreerd.',
        ];
    }
}
