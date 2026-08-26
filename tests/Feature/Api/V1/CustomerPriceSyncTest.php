<?php

use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\Product;
use App\Support\ApiAbility;

it('stores the price agreements that were sent', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['article_number' => 'ART-1']);

    $this->putJson("/api/v1/customers/{$customer->id}/prices", [
        'prices' => [
            ['article_number' => 'ART-1', 'custom_price' => 0.95, 'custom_price_per_kg' => 7.50],
        ],
    ], apiHeaders([ApiAbility::PricesWrite]))->assertOk();

    $price = CustomerProductPrice::where('customer_id', $customer->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect($price->custom_price)->toBe('0.95')
        ->and($price->custom_price_per_kg)->toBe('7.50');
});

it('removes agreements that are missing from the payload', function () {
    $customer = Customer::factory()->create();
    $kept = Product::factory()->create(['article_number' => 'ART-1']);
    $dropped = Product::factory()->create(['article_number' => 'ART-2']);

    foreach ([$kept, $dropped] as $product) {
        CustomerProductPrice::create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'custom_price' => 1.00,
        ]);
    }

    $this->putJson("/api/v1/customers/{$customer->id}/prices", [
        'prices' => [
            ['article_number' => 'ART-1', 'custom_price' => 0.95],
        ],
    ], apiHeaders([ApiAbility::PricesWrite]))->assertOk()->assertJsonCount(1, 'data');

    expect(CustomerProductPrice::where('customer_id', $customer->id)->pluck('product_id')->all())
        ->toBe([$kept->id]);
});

it('clears every agreement when an empty list is sent', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create();

    CustomerProductPrice::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'custom_price' => 1.00,
    ]);

    $this->putJson("/api/v1/customers/{$customer->id}/prices", [
        'prices' => [],
    ], apiHeaders([ApiAbility::PricesWrite]))->assertOk();

    expect(CustomerProductPrice::where('customer_id', $customer->id)->count())->toBe(0);
});

it('leaves other customers alone', function () {
    $customer = Customer::factory()->create();
    $other = Customer::factory()->create();
    $product = Product::factory()->create(['article_number' => 'ART-1']);

    CustomerProductPrice::create([
        'customer_id' => $other->id,
        'product_id' => $product->id,
        'custom_price' => 2.00,
    ]);

    $this->putJson("/api/v1/customers/{$customer->id}/prices", [
        'prices' => [],
    ], apiHeaders([ApiAbility::PricesWrite]))->assertOk();

    expect(CustomerProductPrice::where('customer_id', $other->id)->count())->toBe(1);
});

it('rejects an unknown article number', function () {
    $customer = Customer::factory()->create();

    $this->putJson("/api/v1/customers/{$customer->id}/prices", [
        'prices' => [
            ['article_number' => 'BESTAAT-NIET', 'custom_price' => 1.00],
        ],
    ], apiHeaders([ApiAbility::PricesWrite]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('prices.0.article_number');
});

it('rejects a row without any price', function () {
    $customer = Customer::factory()->create();
    Product::factory()->create(['article_number' => 'ART-1']);

    $this->putJson("/api/v1/customers/{$customer->id}/prices", [
        'prices' => [
            ['article_number' => 'ART-1'],
        ],
    ], apiHeaders([ApiAbility::PricesWrite]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('prices.0');
});

it('rejects the same article twice in one payload', function () {
    $customer = Customer::factory()->create();
    Product::factory()->create(['article_number' => 'ART-1']);

    $this->putJson("/api/v1/customers/{$customer->id}/prices", [
        'prices' => [
            ['article_number' => 'ART-1', 'custom_price' => 1.00],
            ['article_number' => 'ART-1', 'custom_price' => 2.00],
        ],
    ], apiHeaders([ApiAbility::PricesWrite]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('prices.1.article_number');
});

it('does not accept price changes on a token without the price ability', function () {
    $customer = Customer::factory()->create();

    $this->putJson("/api/v1/customers/{$customer->id}/prices", [
        'prices' => [],
    ], apiHeaders([ApiAbility::CustomersWrite]))->assertForbidden();
});
