<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'photo' => [
                $this->isMethod('POST') ? 'required' : 'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120', // 5MB
            ],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'description' => ['required', 'string', 'max:5000'],
            'ingredients' => ['nullable', 'string', 'max:1000'],
            'allergens' => ['nullable', 'string', 'max:1000'],
            'nutrition_facts.energy' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'nutrition_facts.fat' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.saturated_fat' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.carbohydrates' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.sugars' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.protein' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.salt' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'nutrition_facts.fiber' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'weight' => ['nullable', 'string', 'max:50'],
            'article_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'article_number')->ignore($productId),
            ],
            'in_stock' => ['required', 'boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'categorie',
            'title' => 'naam',
            'photo' => 'foto',
            'price' => 'prijs',
            'description' => 'omschrijving',
            'ingredients' => 'ingrediënten',
            'allergens' => 'allergenen',
            'nutrition_facts.energy' => 'energie',
            'nutrition_facts.fat' => 'vet',
            'nutrition_facts.saturated_fat' => 'verzadigd vet',
            'nutrition_facts.carbohydrates' => 'koolhydraten',
            'nutrition_facts.sugars' => 'suikers',
            'nutrition_facts.protein' => 'eiwitten',
            'nutrition_facts.salt' => 'zout',
            'nutrition_facts.fiber' => 'vezel',
            'weight' => 'gewicht',
            'article_number' => 'artikelnummer',
            'in_stock' => 'op voorraad',
            'is_active' => 'actief',
        ];
    }
}
