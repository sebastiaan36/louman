<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A Dutch VAT number: NL, nine digits, a B and two digits — NL123456789B01.
 *
 * Says what is wrong with the value that was typed instead of only repeating
 * the format, because the most common mistakes are leaving off the NL or the
 * B-suffix and a generic message does not point that out.
 */
class DutchVatNumber implements ValidationRule
{
    private const Pattern = '/^NL[0-9]{9}B[0-9]{2}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_string($value) ? strtoupper(str_replace([' ', '.', '-'], '', $value)) : '';

        if (preg_match(self::Pattern, $value)) {
            return;
        }

        // Alleen de landcode ontbreekt: dan weten we precies wat eraan moet.
        if (preg_match('/^[0-9]{9}B[0-9]{2}$/', $value)) {
            $fail("Het BTW-nummer moet met NL beginnen. Vul NL{$value} in.");

            return;
        }

        if (! str_starts_with($value, 'NL')) {
            $fail('Het BTW-nummer moet met NL beginnen, bijvoorbeeld NL123456789B01.');

            return;
        }

        if (! str_contains(substr($value, 2), 'B')) {
            $fail('In het BTW-nummer ontbreekt de B. Na de negen cijfers volgt een B met twee cijfers, bijvoorbeeld NL123456789B01.');

            return;
        }

        if (preg_match('/^NL([0-9]+)B([0-9]*)$/', $value, $match)) {
            $cijfers = strlen($match[1]);
            $slot = strlen($match[2]);

            if ($cijfers !== 9) {
                $fail("Na NL horen negen cijfers te staan, u vulde er {$cijfers} in. Bijvoorbeeld NL123456789B01.");

                return;
            }

            if ($slot !== 2) {
                $fail("Na de B horen twee cijfers te staan, u vulde er {$slot} in. Bijvoorbeeld NL123456789B01.");

                return;
            }
        }

        $fail('Vul een geldig Nederlands BTW-nummer in, bijvoorbeeld NL123456789B01.');
    }
}
