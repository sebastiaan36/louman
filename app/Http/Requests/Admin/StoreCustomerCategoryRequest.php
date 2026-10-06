<?php

namespace App\Http\Requests\Admin;

use App\Models\CustomerCategory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCustomerCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }

    /**
     * Customers store the slug derived from the name, so that slug must be
     * usable and unique as well as the name itself.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique(CustomerCategory::class, 'name'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $slug = $this->slug();

                    if ($slug === '') {
                        $fail('Vul een naam met letters of cijfers in.');
                    } elseif (CustomerCategory::query()->where('slug', $slug)->exists()) {
                        $fail('Er bestaat al een categorie met deze naam.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vul een naam in.',
            'name.max' => 'De naam mag maximaal 50 tekens lang zijn.',
            'name.unique' => 'Er bestaat al een categorie met deze naam.',
        ];
    }

    /**
     * The value a customer stores for this category.
     */
    public function slug(): string
    {
        return Str::slug((string) $this->input('name'));
    }
}
