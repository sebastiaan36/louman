<?php

namespace App\Http\Requests\Customer;

use App\Concerns\CustomerValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerProfileUpdateRequest extends FormRequest
{
    use CustomerValidationRules, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isCustomer();
    }

    /**
     * Tidy up spacing and casing before the rules run.
     */
    protected function prepareForValidation(): void
    {
        $this->normaliseCustomerInput();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customer = $this->user()->customer;

        return [
            'company_name' => $this->companyNameRules(),
            'email' => $this->emailRules($this->user()->id),
            'kvk_number' => $this->kvkNumberUpdateRules($customer->id),
            'vat_number' => $this->vatNumberRules(),
            'contact_person' => $this->contactPersonRules(),
            'phone_number' => $this->phoneNumberRules(),
            'street_name' => $this->streetNameRules(),
            'house_number' => $this->houseNumberRules(),
            'postal_code' => $this->postalCodeRules(),
            'city' => $this->cityRules(),
            'bank_account' => $this->bankAccountRules(),
            'packing_slip_email' => ['nullable', 'email', 'max:255'],
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
            'company_name' => 'bedrijfsnaam',
            'email' => 'e-mailadres',
            'kvk_number' => 'KvK nummer',
            'vat_number' => 'BTW nummer',
            'contact_person' => 'contactpersoon',
            'phone_number' => 'telefoonnummer',
            'street_name' => 'straatnaam',
            'house_number' => 'huisnummer',
            'postal_code' => 'postcode',
            'city' => 'plaats',
            'bank_account' => 'rekeningnummer',
            'packing_slip_email' => 'pakbon e-mailadres',
        ];
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
            'packing_slip_email.email' => 'Vul een geldig pakbon e-mailadres in.',
        ];
    }
}
