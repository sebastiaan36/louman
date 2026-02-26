<?php

use App\Models\Customer;

test('admin ziet lijst van goedgekeurde klanten', function () {
    $admin = adminUser();
    $approved = approvedCustomer();
    pendingCustomer(); // mag niet verschijnen

    $this->actingAs($admin)
        ->get('/admin/customers')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Customers')
            ->has('customers', 1)
            ->where('customers.0.id', $approved->id)
        );
});

test('admin ziet klantdetails', function () {
    $admin = adminUser();
    $customer = approvedCustomer();

    $this->actingAs($admin)
        ->get("/admin/customers/{$customer->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/CustomerDetail')
            ->where('customer.id', $customer->id)
            ->where('customer.company_name', $customer->company_name)
        );
});

test('admin kan klantgegevens updaten', function () {
    $admin = adminUser();
    $customer = approvedCustomer();

    $this->actingAs($admin)
        ->patch("/admin/customers/{$customer->id}", [
            'company_name' => 'Nieuw Bedrijf BV',
            'contact_person' => $customer->contact_person,
            'phone_number' => $customer->phone_number,
            'kvk_number' => $customer->kvk_number,
            'vat_number' => $customer->vat_number,
            'bank_account' => $customer->bank_account,
            'street_name' => $customer->street_name,
            'house_number' => $customer->house_number,
            'postal_code' => $customer->postal_code,
            'city' => $customer->city,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($customer->fresh()->company_name)->toBe('Nieuw Bedrijf BV');
});

test('admin kan klantcategorie en korting updaten', function () {
    $admin = adminUser();
    $customer = approvedCustomer();

    $this->actingAs($admin)
        ->patch("/admin/customers/{$customer->id}/category-discount", [
            'customer_category' => 'horeca',
            'discount_percentage' => '5',
            'delivery_day' => 'dinsdag',
            'show_on_map' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($customer->fresh()->customer_category)->toBe('horeca');
    expect((float) $customer->fresh()->discount_percentage)->toBe(5.0);
});

test('niet-admin heeft geen toegang tot klantbeheer', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->get('/admin/customers')
        ->assertStatus(403);
});
