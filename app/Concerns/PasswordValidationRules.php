<?php

namespace App\Concerns;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'current_password'];
    }

    /**
     * Get Dutch validation messages for the password rules. The complexity
     * requirements (length, mixed case, numbers, symbols, uncompromised) are
     * only enforced in production via Password::defaults().
     *
     * @return array<string, string>
     */
    protected function passwordMessages(): array
    {
        return [
            'password.required' => 'Vul een wachtwoord in.',
            'password.confirmed' => 'De wachtwoordbevestiging komt niet overeen.',
            'password.min' => 'Het wachtwoord moet minimaal 12 tekens bevatten.',
            'password.mixed' => 'Gebruik zowel hoofdletters als kleine letters.',
            'password.letters' => 'Gebruik minimaal één letter.',
            'password.numbers' => 'Gebruik minimaal één cijfer.',
            'password.symbols' => 'Gebruik minimaal één symbool (bijv. ! @ # $).',
            'password.uncompromised' => 'Dit wachtwoord komt voor in een bekend datalek. Kies een ander wachtwoord.',
        ];
    }
}
