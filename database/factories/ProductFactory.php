<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'title' => fake()->words(3, true),
            'photo' => null,
            'price' => fake()->randomFloat(2, 1, 50),
            'description' => fake()->paragraph(),
            'ingredients' => ['water', 'zout', 'peper'],
            'allergens' => ['gluten'],
            'nutrition_facts' => null,
            'weight' => '500g',
            'article_number' => fake()->unique()->numerify('ART-####'),
            'in_stock' => true,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_stock' => false,
        ]);
    }
}
