<?php

use App\Models\Category;
use App\Models\Product;

test('admin kan subcategorie aanmaken onder een hoofdcategorie', function () {
    $admin = adminUser();
    $parent = Category::factory()->create(['name' => 'Vleeswaren']);

    $this->actingAs($admin)
        ->post('/admin/categories', [
            'parent_id' => $parent->id,
            'name' => 'Worst',
            'sort_order' => 0,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('categories', [
        'parent_id' => $parent->id,
        'name' => 'Worst',
    ]);
});

test('admin ziet subcategorieën genest onder hoofdcategorie', function () {
    $admin = adminUser();
    $parent = Category::factory()->create(['name' => 'Vleeswaren']);
    $child = Category::factory()->create(['name' => 'Worst', 'parent_id' => $parent->id]);

    $this->actingAs($admin)
        ->get('/admin/categories')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Categories')
            ->has('categories', 1)
            ->where('categories.0.id', $parent->id)
            ->has('categories.0.children', 1)
            ->where('categories.0.children.0.id', $child->id)
        );
});

test('admin kan product koppelen aan subcategorie', function () {
    $admin = adminUser();
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $product = Product::factory()->create(['category_id' => $parent->id]);

    $this->actingAs($admin)
        ->patch("/admin/products/{$product->id}", [
            'category_id' => $parent->id,
            'subcategory_id' => $child->id,
            'title' => $product->title,
            'price' => $product->price,
            'description' => $product->description,
            'article_number' => $product->article_number,
            'in_stock' => $product->in_stock,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'subcategory_id' => $child->id,
    ]);
});

test('hoofdcategorie met subcategorieën kan niet verwijderd worden', function () {
    $admin = adminUser();
    $parent = Category::factory()->create();
    Category::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs($admin)
        ->delete("/admin/categories/{$parent->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('categories', ['id' => $parent->id]);
});
