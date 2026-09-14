<?php

use App\Models\Customer;
use App\Models\Order;

test('een beheerder krijgt de tellers voor het menu mee', function () {
    Customer::factory()->count(2)->create(['approved_at' => null]);
    $approved = Customer::factory()->approved()->create();
    Order::factory()->count(3)->create(['customer_id' => $approved->id, 'status' => 'pending']);
    Order::factory()->create(['customer_id' => $approved->id, 'status' => 'confirmed']);
    Order::factory()->create(['customer_id' => $approved->id, 'status' => 'completed']);

    $this->actingAs(adminUser())
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('adminCounts.pendingCustomers', 2)
            ->where('adminCounts.pendingOrders', 3)
        );
});

test('een klant krijgt geen tellers mee', function () {
    $customer = Customer::factory()->approved()->create();

    $this->actingAs($customer->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('adminCounts', null));
});

test('het menu toont de tellers achter Wachtende klanten en Bestellingen', function () {
    $sidebar = file_get_contents(dirname(__DIR__, 3).'/resources/js/components/AppSidebar.vue');

    expect($sidebar)
        ->toContain("title: 'Wachtende klanten',\n        href: admin.customers.pending(),\n        icon: Users,\n        badge: countBadge(adminCounts.value?.pendingCustomers),")
        ->toContain("title: 'Bestellingen',\n        href: '/admin/orders',\n        icon: ShoppingCart,\n        badge: countBadge(adminCounts.value?.pendingOrders),");
});

test('een teller op nul wordt niet getoond', function () {
    $sidebar = file_get_contents(dirname(__DIR__, 3).'/resources/js/components/AppSidebar.vue');

    expect($sidebar)->toContain('count && count > 0 ? count : undefined');
});
