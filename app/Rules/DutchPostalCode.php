<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A Dutch postal code: four digits not starting with a zero, then two letters.
 */
class DutchPostalCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_string($value) ? strtoupper(str_replace([' ', '-'], '', $value)) : '';

        if (preg_match('/^[1-9][0-9]{3}[A-Z]{2}$/', $value)) {
            return;
        }

        if (preg_match('/^[0-9]{4}$/', $value)) {
            $fail('Vul ook de twee letters van de postcode in, bijvoorbeeld 1234 AB.');

            return;
        }

        if (preg_match('/^0/', $value)) {
            $fail('Een Nederlandse postcode begint niet met een 0. Bijvoorbeeld 1234 AB.');

            return;
        }

        $fail('Vul een geldige Nederlandse postcode in: vier cijfers en twee letters, bijvoorbeeld 1234 AB.');
    }
}
