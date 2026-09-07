<?php

namespace App\Concerns;

use App\Models\Customer;
use App\Rules\DutchIban;
use App\Rules\DutchPhoneNumber;
use App\Rules\DutchPostalCode;
use App\Rules\DutchVatNumber;
use App\Rules\KvkNumber;
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
        return ['required', 'string', new DutchPhoneNumber];
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
        return ['required', 'string', new DutchPostalCode];
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
            new KvkNumber,
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
        return ['required', 'string', new DutchIban];
    }

    /**
     * Get the validation rules used to validate customer VAT number.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function vatNumberRules(): array
    {
        return ['required', 'string', new DutchVatNumber];
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
            new KvkNumber,
        ];

        if ($ignoreCustomerId) {
            $rules[] = Rule::unique(Customer::class, 'kvk_number')->ignore($ignoreCustomerId);
        } else {
            $rules[] = Rule::unique(Customer::class, 'kvk_number');
        }

        return $rules;
    }

    /**
     * Tidy up what the customer typed before validating it, so a value that is
     * unmistakably right but written differently is accepted and stored in one
     * consistent form: NL91 ABNA 0417 1643 00 and nl91abna0417164300 both
     * become NL91ABNA0417164300.
     *
     * Only formatting is touched. A missing NL prefix is a mistake, not a
     * notation, and stays a validation error.
     */
    protected function normaliseCustomerInput(): void
    {
        $strip = fn (?string $value): ?string => is_string($value)
            ? str_replace([' ', '.', '-'], '', trim($value))
            : $value;

        $normalised = array_filter([
            'kvk_number' => $strip($this->input('kvk_number')),
            'vat_number' => strtoupper((string) $strip($this->input('vat_number'))) ?: null,
            'bank_account' => strtoupper((string) $strip($this->input('bank_account'))) ?: null,
            'postal_code' => $this->normalisedPostalCode(),
        ], fn ($value) => $value !== null);

        $this->merge(array_intersect_key($normalised, $this->all()));
    }

    /**
     * A postal code as 1234 AB, whatever spacing or casing was typed.
     */
    private function normalisedPostalCode(): ?string
    {
        $value = $this->input('postal_code');

        if (! is_string($value)) {
            return null;
        }

        $compact = strtoupper(str_replace([' ', '-'], '', trim($value)));

        return preg_match('/^([1-9][0-9]{3})([A-Z]{2})$/', $compact, $match)
            ? $match[1].' '.$match[2]
            : $value;
    }
}
