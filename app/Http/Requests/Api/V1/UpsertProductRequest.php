<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

/**
 * Full replace of an article. The external package is leading for products, so
 * fields left out are cleared rather than kept.
 */
class UpsertProductRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $articleNumber = (string) $this->route('article_number');

        return [
            'external_id' => [
                'nullable', 'string', 'max:100',
                Rule::unique('products', 'external_id')
                    ->ignore($articleNumber, 'article_number'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'price_per_kg' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'suggested_retail_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'weight' => ['nullable', 'string', 'max:50'],
            'ingredients' => ['nullable', 'array', 'max:100'],
            'ingredients.*' => ['string', 'max:255'],
            'allergens' => ['nullable', 'array', 'max:100'],
            'allergens.*' => ['string', 'max:255'],
            'nutrition_facts' => ['nullable', 'array'],
            'nutrition_facts.energy' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'nutrition_facts.fat' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.saturated_fat' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.carbohydrates' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.sugars' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.protein' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.salt' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.fiber' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'category' => ['nullable', 'string', 'max:255'],
            'subcategory' => ['nullable', 'string', 'max:255'],
            'in_stock' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'is_private_label' => ['nullable', 'boolean'],
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
            'title.required' => 'Geef een productnaam op.',
            'price.required' => 'Geef een prijs op, exclusief BTW.',
            'in_stock.required' => 'Geef aan of het artikel leverbaar is.',
            'is_active.required' => 'Geef aan of het artikel zichtbaar is in het portaal.',
        ];
    }
}
