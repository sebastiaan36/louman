<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

test('het nieuwe-bestelscherm krijgt het klantnummer mee', function () {
    Customer::factory()->approved()->create([
        'company_name' => 'Koffiehuis De Markt',
        'customer_number' => '487',
    ]);

    $this->actingAs(adminUser())
        ->get('/admin/orders/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('customers.0.company_name', 'Koffiehuis De Markt')
            ->where('customers.0.customer_number', '487')
        );
});

test('er kan op klantnummer gezocht worden', function () {
    $scherm = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CreateOrder.vue');

    expect($scherm)->toContain('c.customer_number?.toLowerCase().includes(q)');
});

test('het besteldetail toont het klantnummer', function () {
    $customer = Customer::factory()->approved()->create([
        'company_name' => 'Koffiehuis De Markt',
        'customer_number' => '487',
    ]);
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs(adminUser())
        ->get("/admin/orders/{$order->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('order.customer.customer_number', '487'));
});

test('een klant zonder nummer levert null op in plaats van een fout', function () {
    $customer = Customer::factory()->approved()->create(['customer_number' => null]);
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs(adminUser())
        ->get("/admin/orders/{$order->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('order.customer.customer_number', null));
});

test('het bestelscherm telt artikelnummers, niet aantallen', function () {
    $scherm = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CreateOrder.vue');

    // Een optelling van item.quantity zou het aantal stuks geven, en dat is
    // juist niet de bedoeling.
    expect($scherm)
        ->toContain('{{ orderedItemCount }}')
        ->toContain("orderedItemCount === 1 ? 'artikelnummer' : 'artikelnummers'");
});

test('overgeslagen regels tellen niet mee', function () {
    $scherm = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CreateOrder.vue');

    // De favorieten staan voorgeladen op 0 en worden overgeslagen door ze zo te
    // laten; de teller hanteert dezelfde grens als het versturen.
    expect($scherm)
        ->toContain('orderItems.value.filter(item => item.quantity >= 1).length')
        ->toContain('quantity: 0,')
        ->toContain('orderItems.value.filter(i => i.quantity >= 1)');
});

test('het aantalveld heeft geen pijltjes meer', function () {
    $scherm = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CreateOrder.vue');
    $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

    expect($scherm)
        ->toContain('no-spinner')
        // De plus- en minknoppen zijn weg; het aantal wordt ingetikt.
        ->not->toContain('item.quantity = Math.max(1, item.quantity - 1)')
        ->not->toContain('item.quantity += 1')
        ->and($css)->toContain('.no-spinner::-webkit-inner-spin-button');
});

test('het overzicht bleef het aantal regels tellen', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();
    OrderItem::factory()->count(3)->sequence(fn ($sequence) => [
        'product_id' => Product::factory()->create()->id,
    ])->create(['order_id' => $order->id, 'quantity' => 5]);

    $this->actingAs(adminUser())
        ->get('/admin/orders')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('orders.data.0.item_count', 3));
});

test('de Quick Order-producten staan op alfabet, niet in volgorde van favoriet maken', function () {
    $scherm = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CreateOrder.vue');

    expect($scherm)
        ->toContain("localeCompare(b.title, 'nl', { sensitivity: 'base' })")
        ->not->toContain('customer.favorite_product_ids
                .map(id => props.products.find(p => p.id === id))');
});

test('de productlijst van het bestelscherm is op titel gesorteerd', function () {
    Product::factory()->create(['title' => 'Zeeuws spek', 'is_active' => true]);
    Product::factory()->create(['title' => 'Achterham', 'is_active' => true]);
    Product::factory()->create(['title' => 'Kiprollade', 'is_active' => true]);

    $this->actingAs(adminUser())
        ->get('/admin/orders/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('products.0.title', 'Achterham')
            ->where('products.1.title', 'Kiprollade')
            ->where('products.2.title', 'Zeeuws spek')
        );
});

test('het bestelscherm krijgt het telefoonnummer van de klant mee en toont het als bel-link', function () {
    Customer::factory()->approved()->create([
        'phone_number' => '020-4470930',
        'mobile_number' => '06-12345678',
    ]);

    $this->actingAs(adminUser())
        ->get('/admin/orders/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('customers.0.phone_number', '020-4470930')
            ->where('customers.0.mobile_number', '06-12345678')
        );

    $scherm = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CreateOrder.vue');

    expect($scherm)
        ->toContain('`tel:${selectedCustomer.phone_number.replace(/[^\d+]/g, \'\')}`')
        ->toContain('`tel:${selectedCustomer.mobile_number.replace(/[^\d+]/g, \'\')}`');
});
