<?php

namespace App\Http\Requests\Auth;

use App\Concerns\CustomerValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
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
        return [
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
            'company_name' => $this->companyNameRules(),
            'contact_person' => $this->contactPersonRules(),
            'phone_number' => $this->phoneNumberRules(),
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'street_name' => $this->streetNameRules(),
            'house_number' => $this->houseNumberRules(),
            'postal_code' => $this->postalCodeRules(),
            'city' => $this->cityRules(),
            'kvk_number' => $this->kvkNumberRules(),
            'bank_account' => $this->bankAccountRules(),
            'vat_number' => $this->vatNumberRules(),
            'show_on_map' => ['nullable', 'boolean'],
            'terms_accepted' => ['accepted'],
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
            'mobile_number' => 'mobiel telefoonnummer',
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
            'kvk_number.unique' => 'Dit KvK nummer is al geregistreerd.',
            'terms_accepted.accepted' => 'Je moet akkoord gaan met de Algemene Voorwaarden om te registreren.',
            ...$this->passwordMessages(),
        ];
    }
}
