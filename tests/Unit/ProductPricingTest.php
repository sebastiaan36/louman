<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('prijs voor groothandel klant is correct', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'customer_category' => 'groothandel',
        'discount_percentage' => 0,
    ]);
    $product = Product::factory()->make([
        'price_groothandel' => '10.00',
        'price_broodjeszaak' => '12.00',
        'price_horeca' => '15.00',
    ]);

    expect($product->getPriceForCustomer($customer))->toBe('10.00');
});

test('prijs voor broodjeszaak klant is correct', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'customer_category' => 'broodjeszaak',
        'discount_percentage' => 0,
    ]);
    $product = Product::factory()->make([
        'price_groothandel' => '10.00',
        'price_broodjeszaak' => '12.00',
        'price_horeca' => '15.00',
    ]);

    expect($product->getPriceForCustomer($customer))->toBe('12.00');
});

test('prijs voor horeca klant is correct', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'customer_category' => 'horeca',
        'discount_percentage' => 0,
    ]);
    $product = Product::factory()->make([
        'price_groothandel' => '10.00',
        'price_broodjeszaak' => '12.00',
        'price_horeca' => '15.00',
    ]);

    expect($product->getPriceForCustomer($customer))->toBe('15.00');
});

test('korting wordt correct toegepast', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'customer_category' => 'groothandel',
        'discount_percentage' => '5', // valid enum value
    ]);
    $product = Product::factory()->make(['price_groothandel' => '100.00']);

    expect($product->getPriceForCustomer($customer))->toBe('95.00');
});

test('BTW percentage is 9 procent', function () {
    expect(Product::getVatRate())->toBe(0.09);
});

test('BTW berekening is correct', function () {
    expect(Product::calculateVat(100.0))->toBe(9.0);
});

test('prijs inclusief BTW is correct', function () {
    expect(round(Product::calculatePriceInclVat(100.0), 2))->toBe(109.0);
});
