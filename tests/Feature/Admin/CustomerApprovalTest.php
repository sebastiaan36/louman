<?php

use App\Models\Customer;
use App\Notifications\CustomerApproved;
use Illuminate\Support\Facades\Notification;

test('gast wordt doorgestuurd naar login', function () {
    $this->get('/admin/customers/pending')->assertRedirect('/login');
});

test('klant heeft geen toegang tot admin pagina', function () {
    $customer = approvedCustomer();
    $this->actingAs($customer->user)
        ->get('/admin/customers/pending')
        ->assertStatus(403);
});

test('admin ziet lijst van pending klanten', function () {
    $admin = adminUser();
    $pending = pendingCustomer();
    approvedCustomer(); // deze mag niet verschijnen

    $this->actingAs($admin)
        ->get('/admin/customers/pending')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/PendingCustomers')
            ->has('customers', 1)
            ->where('customers.0.id', $pending->id)
        );
});

test('admin kan klant goedkeuren', function () {
    Notification::fake();

    $admin = adminUser();
    $customer = pendingCustomer();

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/approve", [
            'customer_category' => 'groothandel',
            'discount_percentage' => '0',
            'delivery_day' => 'maandag',
        ])
        ->assertRedirect();

    expect($customer->fresh()->isApproved())->toBeTrue();
    expect($customer->fresh()->customer_category)->toBe('groothandel');

    Notification::assertSentTo($customer->user, CustomerApproved::class);
});

test('admin kan klant niet dubbel goedkeuren', function () {
    $admin = adminUser();
    $customer = approvedCustomer();

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/approve", [
            'customer_category' => 'groothandel',
            'discount_percentage' => '0',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('goedkeuring vereist een klantcategorie', function () {
    $admin = adminUser();
    $customer = pendingCustomer();

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/approve", [
            'discount_percentage' => '0',
        ])
        ->assertSessionHasErrors('customer_category');
});
