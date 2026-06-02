<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;

test('admin kan klant deactiveren', function () {
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create();

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/deactivate")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($customer->fresh()->isActive())->toBeFalse();
    expect($customer->fresh()->deactivated_at)->not->toBeNull();
});

test('deactiveren van reeds gedeactiveerde klant geeft foutmelding', function () {
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create(['deactivated_at' => now()]);

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/deactivate")
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('admin kan klant activeren', function () {
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create(['deactivated_at' => now()]);

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/activate")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($customer->fresh()->isActive())->toBeTrue();
    expect($customer->fresh()->deactivated_at)->toBeNull();
});

test('activeren van reeds actieve klant geeft foutmelding', function () {
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create();

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/activate")
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('admin kan klant zonder bestellingen verwijderen', function () {
    $admin = adminUser();
    $user = customerUser();
    $customer = Customer::factory()->approved()->create(['user_id' => $user->id]);

    $this->actingAs($admin)
        ->delete("/admin/customers/{$customer->id}")
        ->assertRedirect(route('admin.customers.index'))
        ->assertSessionHas('success');

    expect(Customer::find($customer->id))->toBeNull();
    expect(User::find($user->id))->toBeNull();
});

test('klant met bestellingen kan niet verwijderd worden', function () {
    $admin = adminUser();
    $customer = Customer::factory()->approved()->create();
    Order::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($admin)
        ->delete("/admin/customers/{$customer->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Customer::find($customer->id))->not->toBeNull();
});

test('niet-admin kan klant niet deactiveren', function () {
    $user = customerUser();
    $customer = Customer::factory()->approved()->create();

    $this->actingAs($user)
        ->post("/admin/customers/{$customer->id}/deactivate")
        ->assertForbidden();

    expect($customer->fresh()->isActive())->toBeTrue();
});

test('gedeactiveerde klant wordt uitgelogd en geblokkeerd uit klantomgeving', function () {
    $user = customerUser();
    Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'deactivated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/customer/products')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('actieve goedgekeurde klant heeft normale toegang tot klantomgeving', function () {
    $user = customerUser();
    Customer::factory()->approved()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/customer/products')
        ->assertOk();
});
