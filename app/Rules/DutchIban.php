<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A Dutch IBAN: NL, two check digits, four bank letters and ten digits.
 * Spaces and lower case are accepted; banks print the number both ways.
 */
class DutchIban implements ValidationRule
{
    private const Pattern = '/^NL[0-9]{2}[A-Z]{4}[0-9]{10}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_string($value) ? strtoupper(str_replace([' ', '.', '-'], '', $value)) : '';

        if (preg_match(self::Pattern, $value)) {
            return;
        }

        if (! str_starts_with($value, 'NL')) {
            $fail('Het rekeningnummer moet met NL beginnen, bijvoorbeeld NL91ABNA0417164300. Buitenlandse rekeningnummers kunnen hier niet worden ingevuld.');

            return;
        }

        $lengte = strlen($value);

        if ($lengte !== 18) {
            $fail("Een Nederlands IBAN bestaat uit 18 tekens, u vulde er {$lengte} in. Bijvoorbeeld NL91ABNA0417164300.");

            return;
        }

        $fail('Vul een geldig Nederlands IBAN in, bijvoorbeeld NL91ABNA0417164300: NL, twee cijfers, vier letters van de bank en tien cijfers.');
    }
}
