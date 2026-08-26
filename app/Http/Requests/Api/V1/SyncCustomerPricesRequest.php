<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;

/**
 * The complete set of price agreements for one customer. Whatever is sent
 * becomes the truth: agreements that are missing from the payload are removed,
 * so an empty list clears every deviating price for this customer.
 */
class SyncCustomerPricesRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prices' => ['present', 'array', 'max:2000'],
            'prices.*.article_number' => ['required', 'string', 'exists:products,article_number'],
            'prices.*.custom_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'prices.*.custom_price_per_kg' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    /**
     * Reject rows that carry no price at all, and duplicate articles.
     */
    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            /** @var array<int, array{article_number?: string, custom_price?: mixed, custom_price_per_kg?: mixed}> $rows */
            $rows = $this->input('prices', []);

            if (! is_array($rows)) {
                return;
            }

            $seen = [];

            foreach ($rows as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                if (blank($row['custom_price'] ?? null) && blank($row['custom_price_per_kg'] ?? null)) {
                    $validator->errors()->add(
                        "prices.{$index}",
                        'Geef minimaal een custom_price of een custom_price_per_kg op.',
                    );
                }

                $articleNumber = $row['article_number'] ?? null;

                if (is_string($articleNumber)) {
                    if (isset($seen[$articleNumber])) {
                        $validator->errors()->add(
                            "prices.{$index}.article_number",
                            "Artikel {$articleNumber} komt meerdere keren voor in deze lijst.",
                        );
                    }

                    $seen[$articleNumber] = true;
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prices.present' => 'Stuur een prices-lijst mee, ook wanneer die leeg is.',
            'prices.*.article_number.exists' => 'Onbekend artikelnummer. Stuur het artikel eerst door.',
        ];
    }
}
