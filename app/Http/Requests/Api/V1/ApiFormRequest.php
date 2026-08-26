<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base request for the integration API. Access is decided by the token's
 * abilities in middleware, so authorization here is always granted.
 *
 * Every payload is checked against the rules twice: values must be valid, and
 * top-level keys the endpoint does not know are rejected rather than silently
 * dropped. A typo in a field name then surfaces immediately instead of leaving
 * the data quietly unchanged.
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reject payloads containing keys this endpoint does not accept.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $unknown = array_diff(
                array_keys($this->all()),
                $this->acceptedKeys(),
            );

            foreach ($unknown as $key) {
                $validator->errors()->add(
                    (string) $key,
                    "Het veld '{$key}' wordt door dit endpoint niet geaccepteerd.",
                );
            }
        });
    }

    /**
     * The top-level keys the endpoint accepts, derived from its rules.
     *
     * @return list<string>
     */
    protected function acceptedKeys(): array
    {
        $keys = array_map(
            fn (string $rule): string => explode('.', $rule)[0],
            array_keys($this->rules()),
        );

        return array_values(array_unique($keys));
    }
}
