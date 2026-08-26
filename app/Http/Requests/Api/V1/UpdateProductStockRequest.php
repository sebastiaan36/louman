<?php

namespace App\Http\Requests\Api\V1;

/**
 * Availability only. Kept separate from the full article update so a stock
 * feed can run on a short interval without resending the whole article.
 */
class UpdateProductStockRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'in_stock' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'in_stock.required' => 'Geef aan of het artikel leverbaar is.',
        ];
    }
}
