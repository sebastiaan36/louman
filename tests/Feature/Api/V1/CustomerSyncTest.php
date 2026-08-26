<?php

use App\Models\Customer;
use App\Support\ApiAbility;

it('lists customers that do not have a customer number yet', function () {
    Customer::factory()->create(['customer_number' => null, 'company_name' => 'Nieuwe Zaak']);
    Customer::factory()->create(['customer_number' => 'K-001']);

    $response = $this->getJson('/api/v1/customers?unlinked=1', apiHeaders([ApiAbility::CustomersRead]));

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.company_name'))->toBe('Nieuwe Zaak');
});

it('writes back the customer number and external id after registration', function () {
    $customer = Customer::factory()->create(['customer_number' => null]);

    $this->patchJson("/api/v1/customers/{$customer->id}", [
        'customer_number' => 'K-042',
        'external_id' => 'DEB-42',
    ], apiHeaders([ApiAbility::CustomersWrite]))
        ->assertOk()
        ->assertJsonPath('data.customer_number', 'K-042');

    expect($customer->fresh())
        ->customer_number->toBe('K-042')
        ->external_id->toBe('DEB-42')
        ->synced_at->not->toBeNull();
});

it('only updates the fields that were sent', function () {
    $customer = Customer::factory()->create([
        'company_name' => 'Bakkerij Jansen',
        'city' => 'Utrecht',
    ]);

    $this->patchJson("/api/v1/customers/{$customer->id}", [
        'city' => 'Amersfoort',
    ], apiHeaders([ApiAbility::CustomersWrite]))->assertOk();

    expect($customer->fresh())
        ->city->toBe('Amersfoort')
        ->company_name->toBe('Bakkerij Jansen');
});

it('refuses a customer number that is already taken', function () {
    Customer::factory()->create(['customer_number' => 'K-001']);
    $other = Customer::factory()->create(['customer_number' => null]);

    $this->patchJson("/api/v1/customers/{$other->id}", [
        'customer_number' => 'K-001',
    ], apiHeaders([ApiAbility::CustomersWrite]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('customer_number');
});

it('accepts a customer keeping its own customer number', function () {
    $customer = Customer::factory()->create(['customer_number' => 'K-001']);

    $this->patchJson("/api/v1/customers/{$customer->id}", [
        'customer_number' => 'K-001',
        'city' => 'Zwolle',
    ], apiHeaders([ApiAbility::CustomersWrite]))->assertOk();

    expect($customer->fresh()->city)->toBe('Zwolle');
});

it('does not let the external package approve a customer', function () {
    $customer = Customer::factory()->pending()->create();

    $this->patchJson("/api/v1/customers/{$customer->id}", [
        'approved_at' => now()->toIso8601String(),
    ], apiHeaders([ApiAbility::CustomersWrite]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('approved_at');

    expect($customer->fresh()->approved_at)->toBeNull();
});

it('rejects an unknown customer category', function () {
    $customer = Customer::factory()->create();

    $this->patchJson("/api/v1/customers/{$customer->id}", [
        'customer_category' => 'bakkerij',
    ], apiHeaders([ApiAbility::CustomersWrite]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('customer_category');
});

it('exposes the login account and delivery addresses on a single customer', function () {
    $customer = approvedCustomer();
    $customer->deliveryAddresses()->create([
        'name' => 'Filiaal Centrum',
        'street_name' => 'Hoofdstraat',
        'house_number' => '1',
        'postal_code' => '1234 AB',
        'city' => 'Utrecht',
        'is_default' => true,
    ]);

    $this->getJson("/api/v1/customers/{$customer->id}", apiHeaders([ApiAbility::CustomersRead]))
        ->assertOk()
        ->assertJsonPath('data.account.email', $customer->user->email)
        ->assertJsonPath('data.delivery_addresses.0.name', 'Filiaal Centrum');
});
