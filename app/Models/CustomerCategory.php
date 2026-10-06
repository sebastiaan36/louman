<?php

namespace App\Models;

use Database\Factories\CustomerCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class CustomerCategory extends Model
{
    /** @use HasFactory<CustomerCategoryFactory> */
    use HasFactory;

    protected $fillable = ['slug', 'name'];

    /**
     * The customers in this category. Customers store the slug, not the id.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'customer_category', 'slug');
    }

    /**
     * Validation rule: the value is the slug of an existing category.
     */
    public static function rule(): Exists
    {
        return Rule::exists('customer_categories', 'slug');
    }

    /**
     * All categories as options for a choice list, alphabetical.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return static::query()->orderBy('name')->get()
            ->map(fn (CustomerCategory $category): array => ['value' => $category->slug, 'label' => $category->name])
            ->all();
    }
}
