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
