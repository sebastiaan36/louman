<?php

use App\Models\Customer;

test('admin kan een vaste bestelnotitie bij een klant opslaan', function () {
    $customer = Customer::factory()->approved()->create(['company_name' => 'Koffiehuis De Markt']);

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}", [
            'company_name' => 'Koffiehuis De Markt',
            'order_notes' => 'Altijd voor 7 uur bezorgen.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($customer->fresh()->order_notes)->toBe('Altijd voor 7 uur bezorgen.');
});

test('de vaste bestelnotitie is zichtbaar op het klantdetail', function () {
    $customer = Customer::factory()->approved()->create(['order_notes' => 'Achterom afleveren.']);

    $this->actingAs(adminUser())
        ->get("/admin/customers/{$customer->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('customer.order_notes', 'Achterom afleveren.'));
});

test('een vaste bestelnotitie mag niet langer zijn dan 2000 tekens', function () {
    $customer = Customer::factory()->approved()->create();

    $this->actingAs(adminUser())
        ->from("/admin/customers/{$customer->id}")
        ->patch("/admin/customers/{$customer->id}", ['order_notes' => str_repeat('a', 2001)])
        ->assertSessionHasErrors('order_notes');
});

test('het bestelscherm krijgt de vaste bestelnotitie per klant mee', function () {
    Customer::factory()->approved()->create(['order_notes' => 'Achterom afleveren.']);

    $this->actingAs(adminUser())
        ->get('/admin/orders/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('customers.0.order_notes', 'Achterom afleveren.'));
});

test('het bestelscherm vult de bestelnotitie voor en maakt hem weer leeg bij wisselen', function () {
    $scherm = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CreateOrder.vue');

    expect($scherm)
        ->toContain("notes.value = customer.order_notes ?? '';")
        ->toContain('v-model="notes"');

    $clearCustomer = substr($scherm, strpos($scherm, 'const clearCustomer'));
    $clearCustomer = substr($clearCustomer, 0, strpos($clearCustomer, "\n};"));

    expect($clearCustomer)->toContain("notes.value = '';");
});

test('het klantdetail heeft een veld voor de vaste bestelnotitie', function () {
    $detail = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CustomerDetail.vue');

    expect($detail)
        ->toContain('id="order_notes"')
        ->toContain('v-model="form.order_notes"')
        ->toContain('Vaste bestelnotitie');
});
