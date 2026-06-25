<?php

use App\Models\Customer;
use App\Models\Product;

test('klant ziet standaardproducten plus gekoppelde private-labelproducten, niet ongekoppelde', function () {
    $user = customerUser();
    $customer = approvedCustomer($user);

    $standard = Product::factory()->create(['title' => 'Standaard']);
    $linked = Product::factory()->create(['title' => 'Privé gekoppeld', 'is_private_label' => true]);
    $unlinked = Product::factory()->create(['title' => 'Privé ongekoppeld', 'is_private_label' => true]);
    $linked->visibleToCustomers()->attach($customer->id);

    $response = $this->actingAs($user)->get('/customer/products')->assertOk();

    $ids = collect($response->viewData('page')['props']['products'])->pluck('id');
    expect($ids)->toContain($standard->id);
    expect($ids)->toContain($linked->id);
    expect($ids)->not->toContain($unlinked->id);
});

test('private-labelproduct zonder gekoppelde klanten is voor niemand zichtbaar', function () {
    $user = customerUser();
    approvedCustomer($user);
    $orphan = Product::factory()->create(['is_private_label' => true]);

    $response = $this->actingAs($user)->get('/customer/products')->assertOk();
    $ids = collect($response->viewData('page')['props']['products'])->pluck('id');

    expect($ids)->not->toContain($orphan->id);
});

test('directe URL naar een niet-gekoppeld private-labelproduct geeft 404', function () {
    $user = customerUser();
    approvedCustomer($user);
    $product = Product::factory()->create(['is_private_label' => true]);

    $this->actingAs($user)->get("/customer/products/{$product->id}")->assertNotFound();
});

test('directe URL naar een gekoppeld private-labelproduct is toegankelijk', function () {
    $user = customerUser();
    $customer = approvedCustomer($user);
    $product = Product::factory()->create(['is_private_label' => true]);
    $product->visibleToCustomers()->attach($customer->id);

    $this->actingAs($user)->get("/customer/products/{$product->id}")->assertOk();
});

test('klant kan een niet-zichtbaar private-labelproduct niet in de winkelwagen leggen', function () {
    $user = customerUser();
    approvedCustomer($user);
    $product = Product::factory()->create(['is_private_label' => true]);

    $this->actingAs($user)
        ->post("/customer/cart/{$product->id}/add", ['quantity' => 1])
        ->assertSessionHas('error');
});

test('klant kan een niet-zichtbaar private-labelproduct niet favorieten', function () {
    $user = customerUser();
    approvedCustomer($user);
    $product = Product::factory()->create(['is_private_label' => true]);

    $this->actingAs($user)
        ->post("/customer/favorites/{$product->id}/toggle")
        ->assertNotFound();
});

test('isVisibleTo regel klopt op modelniveau', function () {
    $customer = Customer::factory()->approved()->create();
    $standard = Product::factory()->create();
    $private = Product::factory()->create(['is_private_label' => true]);

    expect($standard->isVisibleTo($customer))->toBeTrue();
    expect($private->isVisibleTo($customer))->toBeFalse();

    $private->visibleToCustomers()->attach($customer->id);
    expect($private->fresh()->isVisibleTo($customer))->toBeTrue();
});

test('admin koppelt klanten bij opslaan van een private-labelproduct', function () {
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create();
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/products/{$product->id}", [
            'title' => $product->title,
            'price' => '10.00',
            'description' => 'Beschrijving',
            'article_number' => $product->article_number,
            'in_stock' => '1',
            'is_active' => '1',
            'is_private_label' => '1',
            'visible_customer_ids' => [$customer->id],
        ])
        ->assertRedirect();

    expect($product->fresh()->is_private_label)->toBeTrue();
    expect($product->visibleToCustomers()->pluck('customers.id')->all())->toContain($customer->id);
});

test('private-label uitzetten verwijdert de klantkoppelingen', function () {
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create();
    $product = Product::factory()->create(['is_private_label' => true]);
    $product->visibleToCustomers()->attach($customer->id);

    $this->actingAs($admin)
        ->patch("/admin/products/{$product->id}", [
            'title' => $product->title,
            'price' => '10.00',
            'description' => 'Beschrijving',
            'article_number' => $product->article_number,
            'in_stock' => '1',
            'is_active' => '1',
            'is_private_label' => '0',
        ])
        ->assertRedirect();

    expect($product->fresh()->is_private_label)->toBeFalse();
    expect($product->visibleToCustomers()->count())->toBe(0);
});

test('private label menu-filter toont alleen private-labelproducten', function () {
    $admin = adminUser();
    Product::factory()->create(['title' => 'Gewoon']);
    Product::factory()->create(['title' => 'Privaat', 'is_private_label' => true]);

    $response = $this->actingAs($admin)->get('/admin/products?private_label=1')->assertOk();
    $titles = collect($response->viewData('page')['props']['products'])->pluck('title');

    expect($titles)->toContain('Privaat');
    expect($titles)->not->toContain('Gewoon');
});

test('standaard productenlijst verbergt private-labelproducten', function () {
    $admin = adminUser();
    Product::factory()->create(['title' => 'Gewoon']);
    Product::factory()->create(['title' => 'Privaat', 'is_private_label' => true]);

    $response = $this->actingAs($admin)->get('/admin/products')->assertOk();
    $titles = collect($response->viewData('page')['props']['products'])->pluck('title');

    expect($titles)->toContain('Gewoon');
    expect($titles)->not->toContain('Privaat');
});

test('private-labelproducten zijn uitgesloten van het kortingspercentage', function () {
    $customer = Customer::factory()->approved()->create(['discount_percentage' => 20]);

    $regular = Product::factory()->create(['price' => 100, 'is_private_label' => false]);
    $privateLabel = Product::factory()->create(['price' => 100, 'is_private_label' => true]);

    // Standaardproduct krijgt 20% korting, private label niet.
    expect($regular->getPriceForCustomer($customer))->toBe('80.00');
    expect($privateLabel->getPriceForCustomer($customer))->toBe('100.00');
});

test('een aangepaste prijs op een private-labelproduct blijft ongewijzigd', function () {
    $customer = Customer::factory()->approved()->create(['discount_percentage' => 20]);
    $privateLabel = Product::factory()->create(['price' => 100, 'is_private_label' => true]);
    \App\Models\CustomerProductPrice::create([
        'customer_id' => $customer->id,
        'product_id' => $privateLabel->id,
        'custom_price' => 65,
    ]);
    $customer->load('customProductPrices');

    expect($privateLabel->getPriceForCustomer($customer))->toBe('65.00');
});

test('admin productenlijst bevat de prijs per kg', function () {
    $admin = adminUser();
    Product::factory()->create(['title' => 'Worst', 'price_per_kg' => '8.50']);
    Product::factory()->create(['title' => 'Zonder kg', 'price_per_kg' => null]);

    $products = collect(
        $this->actingAs($admin)->get('/admin/products')->assertOk()->viewData('page')['props']['products']
    )->keyBy('title');

    expect((float) $products['Worst']['price_per_kg'])->toBe(8.5);
    expect($products['Zonder kg']['price_per_kg'])->toBeNull();
});
