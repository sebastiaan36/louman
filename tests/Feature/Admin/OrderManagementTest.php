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
    $pdfMock->shouldReceive('stream')->andReturn(response('', 200));

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

test('productielijst sorteert op artikelnummer in natuurlijke volgorde', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);

    foreach (['100', '2', '10'] as $articleNumber) {
        $product = Product::factory()->create(['article_number' => $articleNumber]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    $captured = null;
    $pdfMock = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    $pdfMock->shouldReceive('stream')->andReturn(response('', 200));

    Pdf::shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function ($view, $data) use (&$captured, $pdfMock) {
            $captured = $data;

            return $pdfMock;
        });

    $this->actingAs($admin)
        ->get('/admin/orders/production-list')
        ->assertOk();

    expect(array_column($captured['products'], 'article_number'))->toBe(['2', '10', '100']);
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
    $pdfMock->shouldReceive('stream')->andReturn(response('', 200));

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

test('bestellingenoverzicht toont het handmatige klantnummer, leeg als niet ingevuld', function () {
    $admin = adminUser();
    $withNumber = approvedCustomer();
    $withNumber->update(['customer_number' => '077']);
    $withoutNumber = approvedCustomer();

    $captured = null;
    $pdfMock = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    $pdfMock->shouldReceive('stream')->andReturn(response('', 200));
    Pdf::shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function ($view, $data) use (&$captured, $pdfMock) {
            $captured = $data;

            return $pdfMock;
        });

    $this->actingAs($admin)
        ->get('/admin/orders/customer-overview')
        ->assertOk();

    $byId = collect($captured['dayGroups'])->flatMap(fn ($customers) => $customers)->keyBy('id');
    expect($byId[$withNumber->id]['number'])->toBe('077');
    expect($byId[$withoutNumber->id]['number'])->toBeNull();
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
    $pdfMock->shouldReceive('stream')->andReturn(response('', 200));

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

test('bestellingenoverzicht markeert ophaalklanten en stuurt klantnummer mee', function () {
    $admin = adminUser();
    $pickupCustomer = Customer::factory()->approved()->create(['delivery_day' => 'ophalen']);
    $deliverCustomer = Customer::factory()->approved()->create(['delivery_day' => 'maandag']);

    $captured = null;
    $pdfMock = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    $pdfMock->shouldReceive('stream')->andReturn(response('', 200));

    Pdf::shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function ($view, $data) use (&$captured, $pdfMock) {
            $captured = $data;

            return $pdfMock;
        });

    $this->actingAs($admin)
        ->get('/admin/orders/customer-overview')
        ->assertOk();

    $all = collect($captured['dayGroups'])->flatMap(fn ($customers) => $customers);

    // Ophaalklant is meegenomen (niet gefilterd) en gemarkeerd
    $pickup = $all->firstWhere('id', $pickupCustomer->id);
    expect($pickup)->not->toBeNull();
    expect($pickup['is_pickup'])->toBeTrue();

    // Bezorgklant is niet als ophalen gemarkeerd
    $deliver = $all->firstWhere('id', $deliverCustomer->id);
    expect($deliver['is_pickup'])->toBeFalse();
});

test('admin order-bewerking gebruikt de aangepaste klantprijs voor nieuwe regels', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $product = Product::factory()->create(['price' => 100]);
    \App\Models\CustomerProductPrice::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'custom_price' => 70,
    ]);
    $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

    $this->actingAs($admin)
        ->patch("/admin/orders/{$order->id}", [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])
        ->assertRedirect();

    $item = $order->items()->first();
    expect((float) $item->price)->toBe(70.0);
    expect((float) $order->fresh()->total)->toBe(140.0);
});

test('order voltooien voor klant zonder account verstuurt geen mail en crasht niet', function () {
    Mail::fake();
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create([
        'user_id' => null,
        'packing_slip_email' => null,
    ]);
    $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);

    $this->actingAs($admin)
        ->patch("/admin/orders/{$order->id}/status", ['status' => 'completed'])
        ->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertNothingSent();
    expect($order->fresh()->status)->toBe('completed');
});

test('bulk voltooien verstuurt verzendmail per order', function () {
    Mail::fake();
    $admin = adminUser();
    $customer = approvedCustomer();
    $first = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);
    $second = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);

    $this->actingAs($admin)
        ->post('/admin/orders/bulk/update-status', [
            'order_ids' => [$first->id, $second->id],
            'status' => 'completed',
        ])
        ->assertRedirect();

    Mail::assertQueued(OrderShipped::class, 2);
    expect($first->fresh()->status)->toBe('completed');
});

test('admin kan een bestelling aanmaken met een gekozen status', function () {
    Mail::fake();

    $admin = adminUser();
    $user = customerUser();
    $customer = Customer::factory()->approved()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['price' => 10, 'in_stock' => true]);

    $this->actingAs($admin)
        ->post('/admin/orders', [
            'customer_id' => $customer->id,
            'delivery_address_id' => null,
            'status' => 'confirmed',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'notes' => null,
        ])
        ->assertRedirect();

    $order = $customer->orders()->latest()->first();
    expect($order->status)->toBe('confirmed');
    expect((float) $order->total)->toBe(20.0);

    // De klant krijgt een bevestigingsmail, ook bij een admin-aangemaakte bestelling.
    Mail::assertSent(\App\Mail\OrderConfirmation::class, fn ($mail) => $mail->order->id === $order->id && $mail->hasTo($user->email));
});

test('admin bestelling aanmaken vereist een geldige status', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $product = Product::factory()->create(['in_stock' => true]);

    $this->actingAs($admin)
        ->post('/admin/orders', [
            'customer_id' => $customer->id,
            'status' => 'iets-ongeldigs',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])
        ->assertSessionHasErrors('status');
});

test('admin haalt de aangepaste prijzen van een klant op via het prices-endpoint', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $product = Product::factory()->create();
    \App\Models\CustomerProductPrice::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'custom_price' => 42.50,
    ]);

    $this->actingAs($admin)
        ->getJson("/admin/orders/customer/{$customer->id}/prices")
        ->assertOk()
        ->assertJsonPath("custom_prices.{$product->id}", '42.50');
});

test('admin kan bestellingen zoeken op klantnummer en bedrijfsnaam', function () {
    $admin = adminUser();
    $a = approvedCustomer();
    $a->update(['company_name' => 'Bakker Bart', 'customer_number' => '101']);
    $b = approvedCustomer();
    $b->update(['company_name' => 'Slager Sjaak', 'customer_number' => '202']);
    $orderA = Order::factory()->create(['customer_id' => $a->id]);
    $orderB = Order::factory()->create(['customer_id' => $b->id]);

    // Zoeken op klantnummer
    $this->actingAs($admin)
        ->get('/admin/orders?search=202')
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $orderB->id)
        );

    // Zoeken op bedrijfsnaam
    $this->actingAs($admin)
        ->get('/admin/orders?search=Bakker')
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $orderA->id)
        );
});

test('aanmaken met "nog een order" maakt de bestelling en gaat terug naar het aanmaakscherm', function () {
    Mail::fake();

    $admin = adminUser();
    $customer = approvedCustomer();
    $product = Product::factory()->create(['price' => 10, 'in_stock' => true]);

    $this->actingAs($admin)
        ->post('/admin/orders', [
            'customer_id' => $customer->id,
            'delivery_address_id' => null,
            'status' => 'confirmed',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'create_another' => true,
        ])
        ->assertRedirect(route('admin.orders.create'));

    expect($customer->orders()->count())->toBe(1);
});
