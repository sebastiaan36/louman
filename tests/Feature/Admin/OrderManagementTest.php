<?php

use App\Mail\OrderShipped;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;

test('admin ziet alle bestellingen', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    Order::factory()->count(3)->create(['customer_id' => $customer->id]);

    $this->actingAs($admin)
        ->get('/admin/orders')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Orders'));
});

test('admin kan filteren op status', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    Order::factory()->pending()->create(['customer_id' => $customer->id]);
    Order::factory()->completed()->create(['customer_id' => $customer->id]);

    $this->actingAs($admin)
        ->get('/admin/orders?status=pending')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Orders')
            ->where('filters.status', 'pending')
        );
});

test('admin ziet besteldetails', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($admin)
        ->get("/admin/orders/{$order->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/OrderDetail')
            ->where('order.id', $order->id)
        );
});

test('admin kan bestelstatus wijzigen', function () {
    Mail::fake();

    $admin = adminUser();
    $customer = approvedCustomer();
    $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

    $this->actingAs($admin)
        ->patch("/admin/orders/{$order->id}/status", ['status' => 'confirmed'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe('confirmed');
    Mail::assertNothingSent();
});

test('verzonden mail wordt verstuurd bij status voltooid', function () {
    Mail::fake();

    $admin = adminUser();
    $customer = approvedCustomer();
    $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);
    $product = Product::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $this->actingAs($admin)
        ->patch("/admin/orders/{$order->id}/status", ['status' => 'completed'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe('completed');

    Mail::assertQueued(OrderShipped::class, function (OrderShipped $mail) use ($order) {
        return $mail->order->id === $order->id;
    });
});

test('verzonden mail gaat naar packing_slip_email als die is ingesteld', function () {
    Mail::fake();

    $admin = adminUser();
    $user = customerUser();
    $customer = Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'packing_slip_email' => 'verzending@klant.nl',
    ]);
    $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);
    $product = Product::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $this->actingAs($admin)
        ->patch("/admin/orders/{$order->id}/status", ['status' => 'completed']);

    Mail::assertQueued(OrderShipped::class, fn ($mail) => $mail->hasTo('verzending@klant.nl'));
});

test('admin kan bestelling aanpassen als niet voltooid', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);
    $product = Product::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($admin)
        ->patch("/admin/orders/{$order->id}", [
            'items' => [
                ['id' => $item->id, 'product_id' => $product->id, 'quantity' => 5],
            ],
            'notes' => 'Aangepaste opmerking',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($item->fresh()->quantity)->toBe(5);
});

test('admin kan voltooide bestelling niet aanpassen', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $order = Order::factory()->completed()->create(['customer_id' => $customer->id]);
    $product = Product::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $this->actingAs($admin)
        ->patch("/admin/orders/{$order->id}", [
            'items' => [['id' => $item->id, 'product_id' => $product->id, 'quantity' => 10]],
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});
