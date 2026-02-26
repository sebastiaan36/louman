<?php

namespace App\Http\Requests\Auth;

use App\Concerns\CustomerValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRegisterRequest extends FormRequest
{
    use CustomerValidationRules, PasswordValidationRules, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
            'company_name' => $this->companyNameRules(),
            'contact_person' => $this->contactPersonRules(),
            'phone_number' => $this->phoneNumberRules(),
            'street_name' => $this->streetNameRules(),
            'house_number' => $this->houseNumberRules(),
            'postal_code' => $this->postalCodeRules(),
            'city' => $this->cityRules(),
            'kvk_number' => $this->kvkNumberRules(),
            'bank_account' => $this->bankAccountRules(),
            'vat_number' => $this->vatNumberRules(),
            'show_on_map' => ['nullable', 'boolean'],
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
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.email' => 'Vul een geldig e-mailadres in.',
            'email.unique' => 'Dit e-mailadres is al in gebruik.',
            'phone_number.regex' => 'Vul een geldig Nederlands telefoonnummer in (bijv. 06-12345678 of 010-1234567).',
            'postal_code.regex' => 'Vul een geldige Nederlandse postcode in (bijv. 1234 AB).',
            'kvk_number.regex' => 'Het KvK nummer moet 8 cijfers bevatten.',
            'kvk_number.unique' => 'Dit KvK nummer is al geregistreerd.',
            'bank_account.regex' => 'Vul een geldig Nederlands IBAN rekeningnummer in (bijv. NL91ABNA0417164300).',
            'vat_number.regex' => 'Vul een geldig Nederlands BTW nummer in (bijv. NL123456789B01).',
        ];
    }
}
