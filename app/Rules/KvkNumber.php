<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A KvK number: exactly eight digits.
 */
class KvkNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_string($value) ? str_replace([' ', '.', '-'], '', $value) : '';

        if (preg_match('/^[0-9]{8}$/', $value)) {
            return;
        }

        if (preg_match('/[^0-9]/', $value)) {
            $fail('Het KvK-nummer mag alleen cijfers bevatten, bijvoorbeeld 12345678.');

            return;
        }

        $lengte = strlen($value);

        $fail("Het KvK-nummer bestaat uit 8 cijfers, u vulde er {$lengte} in. Bijvoorbeeld 12345678.");
    }
}
