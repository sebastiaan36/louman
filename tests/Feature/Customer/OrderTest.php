<?php

use App\Mail\OrderConfirmation;
use App\Mail\OrderPlacedNotification;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;

test('klant kan bestelling plaatsen', function () {
    Mail::fake();

    $user = customerUser();
    $customer = approvedCustomer($user);
    $product = Product::factory()->create();
    $address = DeliveryAddress::factory()->create(['customer_id' => $customer->id]);

    CartItem::create(['customer_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 2]);

    $this->actingAs($user)
        ->post('/customer/orders', [
            'delivery_address_id' => $address->id,
            'notes' => 'Test opmerking',
        ])
        ->assertRedirect();

    expect(Order::where('customer_id', $customer->id)->exists())->toBeTrue();
    expect(CartItem::where('customer_id', $customer->id)->count())->toBe(0);
});

test('bestelling met lege winkelwagen geeft foutmelding', function () {
    $user = customerUser();
    $customer = approvedCustomer($user);
    $address = DeliveryAddress::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($user)
        ->post('/customer/orders', [
            'delivery_address_id' => $address->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('OrderPlacedNotification wordt verstuurd naar admin', function () {
    Mail::fake();

    $user = customerUser();
    $customer = approvedCustomer($user);
    $product = Product::factory()->create();
    $address = DeliveryAddress::factory()->create(['customer_id' => $customer->id]);

    CartItem::create(['customer_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user)
        ->post('/customer/orders', ['delivery_address_id' => $address->id]);

    Mail::assertQueued(OrderPlacedNotification::class, fn ($mail) => $mail->hasTo('info@louman-joraan.nl'));
});

test('OrderConfirmation wordt verstuurd naar klant', function () {
    Mail::fake();

    $user = customerUser();
    $customer = approvedCustomer($user);
    $product = Product::factory()->create();
    $address = DeliveryAddress::factory()->create(['customer_id' => $customer->id]);

    CartItem::create(['customer_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user)
        ->post('/customer/orders', ['delivery_address_id' => $address->id]);

    Mail::assertQueued(OrderConfirmation::class, fn ($mail) => $mail->hasTo($user->email));
});

test('bevestigingsmail gaat naar packing_slip_email als die is ingesteld', function () {
    Mail::fake();

    $user = customerUser();
    $customer = Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'packing_slip_email' => 'pakbon@klant.nl',
    ]);
    $product = Product::factory()->create();
    $address = DeliveryAddress::factory()->create(['customer_id' => $customer->id]);

    CartItem::create(['customer_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user)
        ->post('/customer/orders', ['delivery_address_id' => $address->id]);

    Mail::assertQueued(OrderConfirmation::class, fn ($mail) => $mail->hasTo('pakbon@klant.nl'));
});

test('klant ziet eigen bestellingen', function () {
    $user = customerUser();
    $customer = approvedCustomer($user);
    Order::factory()->count(2)->create(['customer_id' => $customer->id]);

    $this->actingAs($user)
        ->get('/customer/orders')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customer/Orders')
            ->has('orders', 2)
        );
});

test('klant kan geen bestelling van andere klant bekijken', function () {
    $user = customerUser();
    approvedCustomer($user);

    $otherCustomer = approvedCustomer();
    $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);

    $this->actingAs($user)
        ->get("/customer/orders/{$order->id}")
        ->assertStatus(403);
});
