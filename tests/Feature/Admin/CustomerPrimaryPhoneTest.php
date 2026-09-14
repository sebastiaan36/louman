<?php

use App\Models\Customer;

test('het vaste nummer is standaard het hoofdnummer', function () {
    $customer = Customer::factory()->approved()->create([
        'phone_number' => '020-4470930',
        'mobile_number' => '06-12345678',
    ]);

    expect($customer->primary_phone)->toBe('phone')
        ->and($customer->primaryPhoneNumber())->toBe('020-4470930');
});

test('admin kan het mobiele nummer als hoofdnummer aanvinken', function () {
    $customer = Customer::factory()->approved()->create([
        'phone_number' => '020-4470930',
        'mobile_number' => '06-12345678',
    ]);

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}", [
            'company_name' => $customer->company_name,
            'phone_number' => '020-4470930',
            'mobile_number' => '06-12345678',
            'primary_phone' => 'mobile',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($customer->fresh()->primaryPhoneNumber())->toBe('06-12345678');

    $this->actingAs(adminUser())
        ->get("/admin/customers/{$customer->id}")
        ->assertInertia(fn ($page) => $page->where('customer.primary_phone', 'mobile'));
});

test('een onbekende keuze voor het hoofdnummer wordt geweigerd', function () {
    $customer = Customer::factory()->approved()->create();

    $this->actingAs(adminUser())
        ->from("/admin/customers/{$customer->id}")
        ->patch("/admin/customers/{$customer->id}", ['primary_phone' => 'fax'])
        ->assertSessionHasErrors('primary_phone');
});

test('als het hoofdnummer leeg is valt het terug op het andere nummer', function () {
    $customer = Customer::factory()->approved()->create([
        'phone_number' => '020-4470930',
        'mobile_number' => null,
        'primary_phone' => 'mobile',
    ]);

    expect($customer->primaryPhoneNumber())->toBe('020-4470930');
});

test('de rijroute toont het hoofdnummer', function () {
    Customer::factory()->approved()->create([
        'company_name' => 'Koffiehuis De Markt',
        'phone_number' => '020-4470930',
        'mobile_number' => '06-12345678',
        'primary_phone' => 'mobile',
        'delivery_day' => 'maandag',
    ]);

    $this->actingAs(adminUser())
        ->get('/admin/delivery-route?day=maandag')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('customers.0.phone_number', '06-12345678'));

    $this->actingAs(adminUser())
        ->get('/admin/delivery-route?day=all')
        ->assertInertia(fn ($page) => $page->where('dayGroups.0.customers.0.phone_number', '06-12345678'));
});

test('de rijroute-export gebruikt het hoofdnummer', function () {
    Customer::factory()->approved()->create([
        'company_name' => 'Koffiehuis De Markt',
        'phone_number' => '020-4470930',
        'mobile_number' => '06-12345678',
        'primary_phone' => 'mobile',
        'delivery_day' => 'maandag',
    ]);

    $csv = $this->actingAs(adminUser())
        ->get('/admin/delivery-route/export?day=maandag')
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('"Koffiehuis De Markt";06-12345678;maandag')
        ->not->toContain('020-4470930');
});

test('het klantdetail heeft achter beide nummers een keuzerondje voor het hoofdnummer', function () {
    $detail = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CustomerDetail.vue');

    expect(substr_count($detail, 'v-model="form.primary_phone"'))->toBe(2)
        ->and(substr_count($detail, 'Hoofdnummer'))->toBe(2)
        ->and($detail)->toContain('(hoofdnummer)');
});
