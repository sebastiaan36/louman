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

test('de lijst onthoudt de sortering wanneer je zonder querystring terugkeert', function () {
    $admin = adminUser();
    Product::factory()->create(['article_number' => '900']);
    Product::factory()->create(['article_number' => '100']);

    $this->actingAs($admin)->get('/admin/products?sort=article_asc')->assertOk();

    // Terug op de lijst zonder querystring — bijvoorbeeld na opslaan.
    $this->actingAs($admin)
        ->get('/admin/products')
        ->assertInertia(fn ($page) => $page
            ->where('filters.sort', 'article_asc')
            ->where('products.0.article_number', '100')
        );
});

test('een expliciete sortering in de url wint van de onthouden sortering', function () {
    $admin = adminUser();
    Product::factory()->create(['article_number' => '900']);
    Product::factory()->create(['article_number' => '100']);

    $this->actingAs($admin)->get('/admin/products?sort=article_asc')->assertOk();

    $this->actingAs($admin)
        ->get('/admin/products?sort=article_desc')
        ->assertInertia(fn ($page) => $page
            ->where('filters.sort', 'article_desc')
            ->where('products.0.article_number', '900')
        );
});

test('terug naar nieuwste eerst wist de onthouden sortering', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get('/admin/products?sort=article_asc')->assertOk();
    $this->actingAs($admin)->get('/admin/products?sort=newest')->assertOk();

    $this->actingAs($admin)
        ->get('/admin/products')
        ->assertInertia(fn ($page) => $page->where('filters.sort', 'newest'));
});

test('een onbekende sortering valt terug op nieuwste eerst', function () {
    $this->actingAs(adminUser())
        ->get('/admin/products?sort=; DROP TABLE products')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('filters.sort', 'newest'));
});

test('de onthouden sortering geldt ook voor de private label-lijst', function () {
    $admin = adminUser();
    Product::factory()->create(['article_number' => '900', 'is_private_label' => true]);
    Product::factory()->create(['article_number' => '100', 'is_private_label' => true]);

    $this->actingAs($admin)->get('/admin/products?sort=article_asc')->assertOk();

    $this->actingAs($admin)
        ->get('/admin/products?private_label=1')
        ->assertInertia(fn ($page) => $page
            ->where('filters.private_label', true)
            ->where('products.0.article_number', '100')
        );
});
