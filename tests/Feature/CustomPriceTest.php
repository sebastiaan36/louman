<?php

use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\Product;

test('centrale regel: aangepaste prijs wint en negeert korting', function () {
    $customer = Customer::factory()->approved()->create(['discount_percentage' => 10]);
    $product = Product::factory()->create(['price' => 100]);

    // Zonder aangepaste prijs: standaard met korting
    expect($product->getPriceForCustomer($customer))->toBe('90.00');

    CustomerProductPrice::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'custom_price' => 75,
    ]);

    // Met aangepaste prijs: exact die prijs, geen korting
    expect($product->fresh()->getPriceForCustomer($customer->fresh()))->toBe('75.00');
});

test('geen aangepaste prijs = standaardprijs met korting', function () {
    $customer = Customer::factory()->approved()->create(['discount_percentage' => 20]);
    $product = Product::factory()->create(['price' => 50]);

    expect($product->getPriceForCustomer($customer))->toBe('40.00');
});

test('klant ziet aangepaste prijs in de webshop', function () {
    $user = customerUser();
    $customer = approvedCustomer($user);
    $customer->update(['discount_percentage' => 10]);
    $product = Product::factory()->create(['price' => 100]);
    CustomerProductPrice::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'custom_price' => 80,
    ]);

    $response = $this->actingAs($user)->get('/customer/products')->assertOk();
    $shown = collect($response->viewData('page')['props']['products'])->firstWhere('id', $product->id);

    expect($shown['price'])->toBe('80.00');
});

test('admin slaat aangepaste prijs op voor favoriet product', function () {
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create();
    $product = Product::factory()->create(['price' => 100]);
    $customer->favoriteProducts()->attach($product->id);

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/product-prices", [
            'prices' => [['product_id' => $product->id, 'custom_price' => '85.50']],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect((float) $customer->customPriceFor($product->id))->toBe(85.50);
});

test('prijs gelijk aan standaard verwijdert de rij (sparse)', function () {
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create();
    $product = Product::factory()->create(['price' => 100]);
    $customer->favoriteProducts()->attach($product->id);
    CustomerProductPrice::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'custom_price' => 80,
    ]);

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/product-prices", [
            'prices' => [['product_id' => $product->id, 'custom_price' => '100']],
        ])
        ->assertRedirect();

    expect(CustomerProductPrice::where('customer_id', $customer->id)->where('product_id', $product->id)->exists())->toBeFalse();
});

test('lege prijs verwijdert de rij', function () {
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create();
    $product = Product::factory()->create(['price' => 100]);
    $customer->favoriteProducts()->attach($product->id);
    CustomerProductPrice::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'custom_price' => 80,
    ]);

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/product-prices", [
            'prices' => [['product_id' => $product->id, 'custom_price' => null]],
        ])
        ->assertRedirect();

    expect(CustomerProductPrice::where('customer_id', $customer->id)->count())->toBe(0);
});

test('aangepaste prijs alleen toegestaan voor favorieten', function () {
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create();
    $product = Product::factory()->create(['price' => 100]); // geen favoriet

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/product-prices", [
            'prices' => [['product_id' => $product->id, 'custom_price' => '50']],
        ])
        ->assertRedirect();

    expect(CustomerProductPrice::where('customer_id', $customer->id)->count())->toBe(0);
});

test('bestelregel legt de aangepaste prijs vast als snapshot', function () {
    $user = customerUser();
    $customer = approvedCustomer($user);
    $product = Product::factory()->create(['price' => 100, 'in_stock' => true]);
    CustomerProductPrice::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'custom_price' => 70,
    ]);
    $customer->cartItems()->create(['product_id' => $product->id, 'quantity' => 2]);

    $this->actingAs($user)->post('/customer/orders', [])->assertRedirect();

    $orderItem = $customer->orders()->latest()->first()->items()->first();
    expect((float) $orderItem->price)->toBe(70.0);
});
