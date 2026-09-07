<?php

use App\Models\Order;

test('het dashboard telt de bestellingen per status', function () {
    Order::factory()->count(2)->pending()->create();
    Order::factory()->count(3)->confirmed()->create();
    Order::factory()->count(4)->completed()->create();
    Order::factory()->cancelled()->create();

    $this->actingAs(adminUser())
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.pendingOrders', 2)
            ->where('stats.confirmedOrders', 3)
            ->where('stats.completedOrders', 4)
        );
});

test('zonder bestellingen staan de tellers op nul', function () {
    $this->actingAs(adminUser())
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.pendingOrders', 0)
            ->where('stats.confirmedOrders', 0)
            ->where('stats.completedOrders', 0)
        );
});

test('het aantal op een tegel komt overeen met de gefilterde besteloverzicht', function (string $status, int $aantal) {
    Order::factory()->count(2)->pending()->create();
    Order::factory()->count(3)->confirmed()->create();
    Order::factory()->count(4)->completed()->create();

    $admin = adminUser();

    $dashboard = $this->actingAs($admin)->get('/dashboard');
    $teller = $dashboard->viewData('page')['props']['stats'][$status.'Orders'];

    $lijst = $this->actingAs($admin)->get("/admin/orders?status={$status}");
    $rijen = $lijst->viewData('page')['props']['orders']['total'];

    expect($teller)->toBe($aantal)->and($rijen)->toBe($aantal);
})->with([
    ['pending', 2],
    ['confirmed', 3],
    ['completed', 4],
]);

test('de tegels linken naar het besteloverzicht met het juiste filter', function () {
    $dashboard = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/Dashboard.vue');

    expect($dashboard)
        ->toContain("admin.orders.index.url({ query: { status: 'pending' } })")
        ->toContain("admin.orders.index.url({ query: { status: 'confirmed' } })")
        ->toContain("admin.orders.index.url({ query: { status: 'completed' } })");
});
