<?php

use App\Models\Product;

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

test('admin kan product toevoegen aan quick order lijst van klant', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/favorites/{$product->id}/toggle")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($customer->favoriteProducts()->where('product_id', $product->id)->exists())->toBeTrue();
});

test('admin kan product van quick order lijst van klant verwijderen', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $product = Product::factory()->create();
    $customer->favoriteProducts()->attach($product->id);

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/favorites/{$product->id}/toggle")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($customer->favoriteProducts()->where('product_id', $product->id)->exists())->toBeFalse();
});

test('niet-admin kan quick order lijst niet aanpassen', function () {
    $customer = approvedCustomer();
    $product = Product::factory()->create();

    $this->actingAs($customer->user)
        ->post("/admin/customers/{$customer->id}/favorites/{$product->id}/toggle")
        ->assertForbidden();
});

function customerUpdatePayload(\App\Models\Customer $customer, array $overrides = []): array
{
    return array_merge([
        'company_name' => $customer->company_name,
        'contact_person' => $customer->contact_person,
        'phone_number' => $customer->phone_number,
        'kvk_number' => $customer->kvk_number,
        'vat_number' => $customer->vat_number,
        'bank_account' => $customer->bank_account,
        'street_name' => $customer->street_name,
        'house_number' => $customer->house_number,
        'postal_code' => $customer->postal_code,
        'city' => $customer->city,
    ], $overrides);
}

test('admin kan een klantnummer van 3 cijfers instellen', function () {
    $admin = adminUser();
    $customer = approvedCustomer();

    $this->actingAs($admin)
        ->patch("/admin/customers/{$customer->id}", customerUpdatePayload($customer, ['customer_number' => '007']))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($customer->fresh()->customer_number)->toBe('007');
});

test('klantnummer is standaard leeg en mag leeg blijven', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    expect($customer->customer_number)->toBeNull();

    $this->actingAs($admin)
        ->patch("/admin/customers/{$customer->id}", customerUpdatePayload($customer, ['customer_number' => '']))
        ->assertRedirect();

    expect($customer->fresh()->customer_number)->toBeNull();
});

test('klantnummer mag uit 1 tot 4 cijfers bestaan', function () {
    $admin = adminUser();

    foreach (['1', '12', '123', '1234'] as $valid) {
        $customer = approvedCustomer();
        $this->actingAs($admin)
            ->patch("/admin/customers/{$customer->id}", customerUpdatePayload($customer, ['customer_number' => $valid]))
            ->assertSessionHasNoErrors();
        expect($customer->fresh()->customer_number)->toBe($valid);
    }
});

test('een klantnummer van meer dan 4 cijfers of niet-cijfers wordt geweigerd', function () {
    $admin = adminUser();
    $customer = approvedCustomer();

    foreach (['12345', 'abc', '1a2'] as $invalid) {
        $this->actingAs($admin)
            ->patch("/admin/customers/{$customer->id}", customerUpdatePayload($customer, ['customer_number' => $invalid]))
            ->assertSessionHasErrors('customer_number');
    }
});

test('klantnummer moet uniek zijn', function () {
    $admin = adminUser();
    $other = approvedCustomer();
    $other->update(['customer_number' => '123']);
    $customer = approvedCustomer();

    $this->actingAs($admin)
        ->patch("/admin/customers/{$customer->id}", customerUpdatePayload($customer, ['customer_number' => '123']))
        ->assertSessionHasErrors('customer_number');

    expect($customer->fresh()->customer_number)->toBeNull();
});

test('klant kan eigen klantnummer behouden bij update', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $customer->update(['customer_number' => '055']);

    $this->actingAs($admin)
        ->patch("/admin/customers/{$customer->id}", customerUpdatePayload($customer, ['customer_number' => '055', 'company_name' => 'Andere Naam']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($customer->fresh()->company_name)->toBe('Andere Naam');
    expect($customer->fresh()->customer_number)->toBe('055');
});

test('admin kan klanten zoeken op bedrijfsnaam en klantnummer', function () {
    $admin = adminUser();
    $a = approvedCustomer();
    $a->update(['company_name' => 'Bakker Bart', 'customer_number' => '101']);
    $b = approvedCustomer();
    $b->update(['company_name' => 'Slager Sjaak', 'customer_number' => '202']);

    // Zoeken op bedrijfsnaam
    $this->actingAs($admin)
        ->get('/admin/customers?search=Bakker')
        ->assertInertia(fn ($page) => $page
            ->has('customers', 1)
            ->where('customers.0.id', $a->id)
            ->where('filters.search', 'Bakker')
        );

    // Zoeken op klantnummer
    $this->actingAs($admin)
        ->get('/admin/customers?search=202')
        ->assertInertia(fn ($page) => $page
            ->has('customers', 1)
            ->where('customers.0.id', $b->id)
        );
});

test('admin kan klantgegevens opslaan met lege velden', function () {
    $admin = adminUser();
    $customer = approvedCustomer();

    $this->actingAs($admin)
        ->patch("/admin/customers/{$customer->id}", [
            'company_name' => '',
            'contact_person' => '',
            'phone_number' => '',
            'kvk_number' => '',
            'vat_number' => '',
            'bank_account' => '',
            'street_name' => '',
            'house_number' => '',
            'postal_code' => '',
            'city' => '',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $fresh = $customer->fresh();
    expect($fresh->company_name)->toBeNull();
    expect($fresh->kvk_number)->toBeNull();
    expect($fresh->city)->toBeNull();
});

test('admin kan een mobiel telefoonnummer opslaan', function () {
    $admin = adminUser();
    $customer = approvedCustomer();

    $this->actingAs($admin)
        ->patch("/admin/customers/{$customer->id}", customerUpdatePayload($customer, ['mobile_number' => '0612345678']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($customer->fresh()->mobile_number)->toBe('0612345678');
});

test('csv-import werkt het mobiele telefoonnummer bij', function () {
    $admin = adminUser();
    $customer = approvedCustomer();

    $csv = "id;mobile_number\n{$customer->id};0612345678\n";
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('klanten.csv', $csv);

    $this->actingAs($admin)
        ->post('/admin/customers/import', ['csv_file' => $file])
        ->assertRedirect();

    expect($customer->fresh()->mobile_number)->toBe('0612345678');
});
