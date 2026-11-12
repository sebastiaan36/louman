<?php

use App\Models\Customer;

/**
 * @return array<string, mixed>
 */
function customerProfilePayload(Customer $customer, array $overrides = []): array
{
    return array_merge([
        'company_name' => $customer->company_name,
        'email' => $customer->user->email,
        'kvk_number' => $customer->kvk_number,
        'vat_number' => $customer->vat_number,
        'contact_person' => $customer->contact_person,
        'phone_number' => '020-1234567',
        'street_name' => $customer->street_name,
        'house_number' => $customer->house_number,
        'postal_code' => $customer->postal_code,
        'city' => $customer->city,
        'bank_account' => $customer->bank_account,
        'packing_slip_email' => null,
        'invoice_email' => null,
    ], $overrides);
}

test('klant ziet factuur e-mailadres op profielpagina', function () {
    $customer = approvedCustomer();
    $customer->update(['invoice_email' => 'facturen@klant.nl']);

    $this->actingAs($customer->user)
        ->get('/customer/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customer/Profile')
            ->where('customer.invoice_email', 'facturen@klant.nl')
        );
});

test('klant kan factuur e-mailadres instellen en leegmaken', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->patch('/customer/profile', customerProfilePayload($customer, ['invoice_email' => 'facturen@klant.nl']))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($customer->fresh()->invoice_email)->toBe('facturen@klant.nl');

    $this->actingAs($customer->user)
        ->patch('/customer/profile', customerProfilePayload($customer->fresh(), ['invoice_email' => '']))
        ->assertSessionHasNoErrors();

    expect($customer->fresh()->invoice_email)->toBeNull();
});

test('klant krijgt foutmelding bij ongeldig factuur e-mailadres', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->patch('/customer/profile', customerProfilePayload($customer, ['invoice_email' => 'geen-email']))
        ->assertSessionHasErrors(['invoice_email' => 'Vul een geldig factuur e-mailadres in.']);

    expect($customer->fresh()->invoice_email)->toBeNull();
});

test('admin ziet en bewerkt factuur e-mailadres van klant', function () {
    $customer = approvedCustomer();

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}", [
            'company_name' => $customer->company_name,
            'invoice_email' => 'boekhouding@klant.nl',
        ])
        ->assertSessionHasNoErrors();

    expect($customer->fresh()->invoice_email)->toBe('boekhouding@klant.nl');

    $this->actingAs(adminUser())
        ->get("/admin/customers/{$customer->id}")
        ->assertInertia(fn ($page) => $page->where('customer.invoice_email', 'boekhouding@klant.nl'));
});

test('csv-export bevat factuur e-mailadres', function () {
    $customer = approvedCustomer();
    $customer->update(['invoice_email' => 'facturen@klant.nl']);

    $csv = $this->actingAs(adminUser())
        ->get('/admin/customers/export')
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('invoice_email')->toContain('facturen@klant.nl');
});
