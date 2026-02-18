<?php

namespace App\Http\Requests\Customer;

use App\Concerns\CustomerValidationRules;
use App\Concerns\ProfileValidationRules;
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
            'invoice_email' => ['nullable', 'email', 'max:255'],
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
            'invoice_email' => 'factuur e-mailadres',
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
            'kvk_number.regex' => 'Het KvK nummer moet 8 cijfers bevatten.',
            'kvk_number.unique' => 'Dit KvK nummer is al geregistreerd.',
            'vat_number.regex' => 'Vul een geldig Nederlands BTW nummer in (bijv. NL123456789B01).',
            'phone_number.regex' => 'Vul een geldig Nederlands telefoonnummer in (bijv. 06-12345678 of 010-1234567).',
            'postal_code.regex' => 'Vul een geldige Nederlandse postcode in (bijv. 1234 AB).',
            'bank_account.regex' => 'Vul een geldig Nederlands IBAN rekeningnummer in (bijv. NL91ABNA0417164300).',
            'packing_slip_email.email' => 'Vul een geldig pakbon e-mailadres in.',
            'invoice_email.email' => 'Vul een geldig factuur e-mailadres in.',
        ];
    }
}
