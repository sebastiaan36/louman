<?php

use App\Mail\OrderShipped;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
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

test('nieuwe bestelling pagina toont artikelnummers bij producten', function () {
    $admin = adminUser();
    approvedCustomer();
    $product = Product::factory()->create(['article_number' => 'ART-12345', 'weight' => '300 gram']);

    $this->actingAs($admin)
        ->get('/admin/orders/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/CreateOrder')
            ->where('products.0.article_number', 'ART-12345')
            ->where('products.0.weight', '300 gram')
            ->where('products.0.id', $product->id)
        );
});

test('nieuwe bestelling pagina stuurt quick order producten van klant mee', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $product = Product::factory()->create();
    $customer->favoriteProducts()->attach($product->id);

    $this->actingAs($admin)
        ->get('/admin/orders/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/CreateOrder')
            ->where('customers.0.favorite_product_ids', [$product->id])
        );
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
    $product = Product::factory()->create(['weight' => '250 gram']);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $this->actingAs($admin)
        ->get("/admin/orders/{$order->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/OrderDetail')
            ->where('order.id', $order->id)
            ->where('order.items.0.product_weight', '250 gram')
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

    Mail::assertSent(OrderShipped::class, function (OrderShipped $mail) use ($order) {
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

    Mail::assertSent(OrderShipped::class, fn ($mail) => $mail->hasTo('verzending@klant.nl'));
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

test('productielijst bevat alleen bevestigde bestellingen', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $product = Product::factory()->create(['weight' => '500 gram']);

    $pendingOrder = Order::factory()->pending()->create(['customer_id' => $customer->id]);
    OrderItem::factory()->create([
        'order_id' => $pendingOrder->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $confirmedOrder = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);
    OrderItem::factory()->create([
        'order_id' => $confirmedOrder->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $captured = null;
    $pdfMock = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    $pdfMock->shouldReceive('download')->andReturn(response('', 200));

    Pdf::shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function ($view, $data) use (&$captured, $pdfMock) {
            $captured = $data;

            return $pdfMock;
        });

    $this->actingAs($admin)
        ->get('/admin/orders/production-list')
        ->assertOk();

    expect($captured['orderCount'])->toBe(1);
    expect($captured['products'])->toHaveCount(1);
    expect($captured['products'][0]['quantity'])->toBe(3);
    expect($captured['products'][0]['weight'])->toBe('500 gram');
});

test('bestellingenoverzicht bevat alleen bevestigde bestellingen', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $product = Product::factory()->create(['weight' => '1 kilo']);

    $pendingOrder = Order::factory()->pending()->create(['customer_id' => $customer->id]);
    OrderItem::factory()->create([
        'order_id' => $pendingOrder->id,
        'product_id' => $product->id,
        'quantity' => 7,
    ]);

    $confirmedOrder = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);
    OrderItem::factory()->create([
        'order_id' => $confirmedOrder->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $captured = null;
    $pdfMock = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    $pdfMock->shouldReceive('download')->andReturn(response('', 200));

    Pdf::shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function ($view, $data) use (&$captured, $pdfMock) {
            $captured = $data;

            return $pdfMock;
        });

    $this->actingAs($admin)
        ->get('/admin/orders/customer-overview')
        ->assertOk();

    expect($captured['orderCount'])->toBe(1);

    $allProducts = collect($captured['dayGroups'])
        ->flatMap(fn ($customers) => $customers)
        ->flatMap(fn ($customer) => $customer['products']);
    expect($allProducts->sum('quantity'))->toBe(2);
    expect($allProducts->first()['weight'])->toBe('1 kilo');
});

test('bestellingenoverzicht bevat bestelnotities per klant', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $product = Product::factory()->create();

    $order = Order::factory()->confirmed()->create([
        'customer_id' => $customer->id,
        'notes' => 'Graag voor 10:00 leveren',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $captured = null;
    $pdfMock = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    $pdfMock->shouldReceive('download')->andReturn(response('', 200));

    Pdf::shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function ($view, $data) use (&$captured, $pdfMock) {
            $captured = $data;

            return $pdfMock;
        });

    $this->actingAs($admin)
        ->get('/admin/orders/customer-overview')
        ->assertOk();

    $customerWithNotes = collect($captured['dayGroups'])
        ->flatMap(fn ($customers) => $customers)
        ->first(fn ($c) => ! empty($c['notes']));

    expect($customerWithNotes['notes'])->toContain('Graag voor 10:00 leveren');
});
