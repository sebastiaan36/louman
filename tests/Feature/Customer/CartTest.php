<?php

use App\Models\CartItem;
use App\Models\Product;

test('klant kan product aan winkelwagen toevoegen', function () {
    $user = customerUser();
    $customer = approvedCustomer($user);
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->post("/customer/cart/{$product->id}/add", ['quantity' => 2])
        ->assertRedirect();

    expect(CartItem::where('customer_id', $customer->id)->where('product_id', $product->id)->exists())->toBeTrue();
});

test('klant kan hoeveelheid aanpassen', function () {
    $user = customerUser();
    $customer = approvedCustomer($user);
    $product = Product::factory()->create();

    $cartItem = CartItem::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->patch("/customer/cart/{$cartItem->id}", ['quantity' => 5])
        ->assertRedirect();

    expect($cartItem->fresh()->quantity)->toBe(5);
});

test('klant kan product verwijderen uit winkelwagen', function () {
    $user = customerUser();
    $customer = approvedCustomer($user);
    $product = Product::factory()->create();

    $cartItem = CartItem::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->delete("/customer/cart/{$cartItem->id}")
        ->assertRedirect();

    expect(CartItem::find($cartItem->id))->toBeNull();
});

test('klant kan winkelwagen legen', function () {
    $user = customerUser();
    $customer = approvedCustomer($user);
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    CartItem::create(['customer_id' => $customer->id, 'product_id' => $product1->id, 'quantity' => 1]);
    CartItem::create(['customer_id' => $customer->id, 'product_id' => $product2->id, 'quantity' => 2]);

    $this->actingAs($user)
        ->delete('/customer/cart')
        ->assertRedirect();

    expect(CartItem::where('customer_id', $customer->id)->count())->toBe(0);
});

test('klant kan alleen eigen winkelwagenitem aanpassen', function () {
    $user = customerUser();
    approvedCustomer($user);

    $otherUser = customerUser();
    $otherCustomer = approvedCustomer($otherUser);
    $product = Product::factory()->create();

    $cartItem = CartItem::create([
        'customer_id' => $otherCustomer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->patch("/customer/cart/{$cartItem->id}", ['quantity' => 5])
        ->assertStatus(404); // Route binding scopes cartItem to authenticated customer, so 404 not 403
});
