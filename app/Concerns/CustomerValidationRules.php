<?php

namespace App\Concerns;

use App\Models\Customer;
use Illuminate\Validation\Rule;

trait CustomerValidationRules
{
    /**
     * Get the validation rules used to validate customer company name.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function companyNameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate customer contact person.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function contactPersonRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate customer phone number.
     * Accepts Dutch phone numbers in various formats:
     * - Mobile: 06-12345678, 0612345678, +31612345678, +31 6 12345678
     * - Landline: 010-1234567, 0101234567, +31101234567
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function phoneNumberRules(): array
    {
        return [
            'required',
            'string',
            'regex:/^(\+31|0)[\s\-]?[1-9][\s\-]?[0-9][\s\-]?[0-9][\s\-]?[0-9][\s\-]?[0-9][\s\-]?[0-9][\s\-]?[0-9][\s\-]?[0-9][\s\-]?[0-9]$/',
        ];
    }

    /**
     * Get the validation rules used to validate customer street name.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function streetNameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate customer house number.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function houseNumberRules(): array
    {
        return ['required', 'string', 'max:10'];
    }

    /**
     * Get the validation rules used to validate customer postal code.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function postalCodeRules(): array
    {
        return ['required', 'string', 'regex:/^[1-9][0-9]{3}\s?[a-zA-Z]{2}$/'];
    }

    /**
     * Get the validation rules used to validate customer city.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function cityRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate customer KvK number.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function kvkNumberRules(): array
    {
        return [
            'required',
            'string',
            'regex:/^[0-9]{8}$/',
            Rule::unique(Customer::class, 'kvk_number'),
        ];
    }

    /**
     * Get the validation rules used to validate customer bank account (IBAN).
     * Accepts Dutch IBAN format: NL + 2 digits + 4 letters + 10 digits
     * Example: NL91ABNA0417164300
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function bankAccountRules(): array
    {
        return [
            'required',
            'string',
            'regex:/^NL[0-9]{2}[A-Z]{4}[0-9]{10}$/',
        ];
    }

    /**
     * Get the validation rules used to validate customer VAT number.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function vatNumberRules(): array
    {
        return [
            'required',
            'string',
            'regex:/^NL[0-9]{9}B[0-9]{2}$/',
        ];
    }

    /**
     * Get KvK validation rules with optional ignore for updates.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function kvkNumberUpdateRules(?int $ignoreCustomerId = null): array
    {
        $rules = [
            'required',
            'string',
            'regex:/^[0-9]{8}$/',
        ];

        if ($ignoreCustomerId) {
            $rules[] = Rule::unique(Customer::class, 'kvk_number')->ignore($ignoreCustomerId);
        } else {
            $rules[] = Rule::unique(Customer::class, 'kvk_number');
        }

        return $rules;
    }
}
