<?php

use App\Models\Product;

test('niet-goedgekeurde klant heeft geen toegang tot producten', function () {
    $user = customerUser();
    pendingCustomer($user);

    $this->actingAs($user)
        ->get('/customer/products')
        ->assertRedirect();
});

test('gast heeft geen toegang tot producten', function () {
    $this->get('/customer/products')->assertRedirect('/login');
});

test('goedgekeurde klant ziet productlijst', function () {
    $user = customerUser();
    approvedCustomer($user);

    Product::factory()->count(3)->create();
    Product::factory()->inactive()->create(); // mag niet verschijnen

    $this->actingAs($user)
        ->get('/customer/products')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customer/Products')
            ->has('products', 3)
        );
});

test('klant ziet alleen actieve producten', function () {
    $user = customerUser();
    approvedCustomer($user);

    $active = Product::factory()->create(['title' => 'Actief product']);
    Product::factory()->inactive()->create(['title' => 'Inactief product']);

    $this->actingAs($user)
        ->get('/customer/products')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('products', 1));
});

test('klant ziet productdetails', function () {
    $user = customerUser();
    approvedCustomer($user);

    $product = Product::factory()->create();

    $this->actingAs($user)
        ->get("/customer/products/{$product->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customer/ProductDetail')
            ->where('product.id', $product->id)
            ->where('product.title', $product->title)
        );
});

test('prijs per kg wordt meegegeven in het productoverzicht', function () {
    $user = customerUser();
    approvedCustomer($user);

    Product::factory()->create(['price_per_kg' => 12.50]);
    Product::factory()->create(['price_per_kg' => null]);

    $this->actingAs($user)
        ->get('/customer/products')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('products', 2)
            ->where('products.0.price_per_kg', '12.50')
            ->where('products.1.price_per_kg', null)
        );
});

test('admin kan de prijs per kg op een product opslaan', function () {
    $admin = adminUser();
    $product = Product::factory()->create(['price_per_kg' => null]);

    $this->actingAs($admin)
        ->patch("/admin/products/{$product->id}", [
            'title' => $product->title,
            'price' => '10.00',
            'price_per_kg' => '15.95',
            'description' => $product->description,
            'article_number' => $product->article_number,
            'in_stock' => '1',
        ])
        ->assertRedirect();

    expect((float) $product->fresh()->price_per_kg)->toBe(15.95);
});
