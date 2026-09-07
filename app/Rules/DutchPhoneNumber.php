<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A Dutch phone number, mobile or landline, written with or without spaces,
 * dashes or the +31 country code.
 */
class DutchPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_string($value) ? str_replace([' ', '-', '(', ')'], '', $value) : '';

        $national = str_starts_with($value, '+31')
            ? '0'.substr($value, 3)
            : (str_starts_with($value, '0031') ? '0'.substr($value, 4) : $value);

        if (preg_match('/^0[1-9][0-9]{8}$/', $national)) {
            return;
        }

        if (preg_match('/[^0-9+]/', $value)) {
            $fail('Het telefoonnummer mag alleen cijfers bevatten, eventueel met +31 ervoor. Bijvoorbeeld 06-12345678.');

            return;
        }

        if ($national !== '' && ! str_starts_with($national, '0')) {
            $fail('Het telefoonnummer moet met 0 of +31 beginnen, bijvoorbeeld 06-12345678 of 010-1234567.');

            return;
        }

        $cijfers = strlen($national);

        if ($cijfers !== 10) {
            $fail("Een Nederlands telefoonnummer heeft 10 cijfers, u vulde er {$cijfers} in. Bijvoorbeeld 06-12345678 of 010-1234567.");

            return;
        }

        $fail('Vul een geldig Nederlands telefoonnummer in, bijvoorbeeld 06-12345678 of 010-1234567.');
    }
}
