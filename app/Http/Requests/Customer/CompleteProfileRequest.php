<?php

namespace App\Http\Requests\Customer;

use App\Concerns\CustomerValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteProfileRequest extends FormRequest
{
    use CustomerValidationRules;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->role === 'customer' && $user->customer !== null;
    }

    /**
     * Tidy up spacing and casing before the rules run.
     */
    protected function prepareForValidation(): void
    {
        $this->normaliseCustomerInput();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_person' => $this->contactPersonRules(),
            'phone_number' => $this->phoneNumberRules(),
            'street_name' => $this->streetNameRules(),
            'house_number' => $this->houseNumberRules(),
            'postal_code' => $this->postalCodeRules(),
            'city' => $this->cityRules(),
            'kvk_number' => $this->kvkNumberUpdateRules($this->user()->customer->id),
            'bank_account' => $this->bankAccountRules(),
            'vat_number' => $this->vatNumberRules(),
            'show_on_map' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contact_person' => 'contactpersoon',
            'phone_number' => 'telefoonnummer',
            'street_name' => 'straatnaam',
            'house_number' => 'huisnummer',
            'postal_code' => 'postcode',
            'city' => 'plaats',
            'kvk_number' => 'KvK nummer',
            'bank_account' => 'rekeningnummer',
            'vat_number' => 'BTW nummer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kvk_number.unique' => 'Dit KvK nummer is al geregistreerd.',
        ];
    }
}
