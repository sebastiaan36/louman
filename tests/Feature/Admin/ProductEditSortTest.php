<?php

use App\Models\Product;

test('product bewerken-pagina ontvangt de actieve sortering en zoekopdracht', function () {
    $admin = adminUser();
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/products/{$product->id}/edit?sort=article_asc&search=worst")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/ProductForm')
            ->where('filters.sort', 'article_asc')
            ->where('filters.search', 'worst')
        );
});

test('product opslaan keert terug naar de lijst met dezelfde sortering', function () {
    $admin = adminUser();
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/products/{$product->id}?sort=article_asc", [
            'title' => $product->title,
            'price' => '10.00',
            'description' => 'Beschrijving',
            'article_number' => $product->article_number,
            'in_stock' => '1',
            'is_active' => '1',
            'is_private_label' => '0',
        ])
        ->assertRedirect(route('admin.products.index', ['sort' => 'article_asc']));
});
