<?php

use App\Models\Customer;

test('de rijroute geeft het telefoonnummer van elke klant mee', function () {
    Customer::factory()->approved()->create([
        'company_name' => 'Koffiehuis De Markt',
        'phone_number' => '020-4470930',
        'delivery_day' => 'maandag',
        'route_order' => 1,
    ]);

    $this->actingAs(adminUser())
        ->get('/admin/delivery-route?day=maandag')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('customers.0.company_name', 'Koffiehuis De Markt')
            ->where('customers.0.phone_number', '020-4470930')
        );
});

test('ook de weergave van alle dagen bevat het telefoonnummer', function () {
    Customer::factory()->approved()->create([
        'phone_number' => '020-4470930',
        'delivery_day' => 'dinsdag',
        'route_order' => 1,
    ]);

    $this->actingAs(adminUser())
        ->get('/admin/delivery-route?day=all')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('dayGroups.1.day', 'dinsdag')
            ->where('dayGroups.1.customers.0.phone_number', '020-4470930')
        );
});

test('het bestelscherm selecteert de klant uit de url', function () {
    $customer = Customer::factory()->approved()->create();

    $this->actingAs(adminUser())
        ->get("/admin/orders/create?customer={$customer->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('preselectedCustomerId', $customer->id));
});

test('zonder klant in de url wordt er niets voorgeselecteerd', function () {
    $this->actingAs(adminUser())
        ->get('/admin/orders/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('preselectedCustomerId', null));
});

test('een onbekende of niet-goedgekeurde klant wordt genegeerd', function (string $waarde) {
    $this->actingAs(adminUser())
        ->get("/admin/orders/create?customer={$waarde}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('preselectedCustomerId', null));
})->with([
    'onbekend id' => '999999',
    'geen getal' => 'abc',
]);

test('een klant die nog niet is goedgekeurd wordt niet voorgeselecteerd', function () {
    $customer = Customer::factory()->pending()->create();

    $this->actingAs(adminUser())
        ->get("/admin/orders/create?customer={$customer->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('preselectedCustomerId', null));
});

test('de knop op de rijroute opent het bestelscherm met de klant in de url', function () {
    $root = dirname(__DIR__, 3);
    $pagina = file_get_contents($root.'/resources/js/pages/admin/DeliveryRoute.vue');
    $acties = file_get_contents($root.'/resources/js/components/DeliveryRouteCustomerActions.vue');

    // De knop zit in het gedeelde actiecomponent, dat in beide weergaven
    // (per dag en alle dagen) wordt gebruikt.
    expect($acties)
        ->toContain('admin.orders.create.url({ query: { customer: customerId } })')
        ->toContain('Bestelling maken');
    expect(substr_count($pagina, '<DeliveryRouteCustomerActions'))->toBe(2)
        ->and(substr_count($pagina, '`tel:${customer.phone_number'))->toBe(2);
});

test('het bestelscherm selecteert de meegegeven klant bij het laden', function () {
    $scherm = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CreateOrder.vue');

    expect($scherm)
        ->toContain('preselectedCustomerId: number | null;')
        ->toContain('props.customers.find(c => c.id === props.preselectedCustomerId)')
        ->toContain('selectCustomer(preselected);');
});
