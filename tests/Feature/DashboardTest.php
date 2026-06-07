<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('admin dashboard bevat geen omzetcijfers', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('stats.pendingOrders', 0)
            ->missing('stats.currentMonthRevenue')
            ->missing('stats.revenueChangePercentage')
            ->missing('stats.revenueIncreased')
        );
});

test('cijfers-pagina toont alle cijfers inclusief omzet voor admin', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->get('/admin/cijfers')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Statistics')
            ->has('stats.currentMonthRevenue')
            ->has('stats.revenueChangePercentage')
            ->has('stats.pendingOrders')
            ->has('stats.totalCustomers')
            ->has('stats.currentYearRevenue')
            ->has('stats.ordersThisYear')
            ->has('chart', 12)
        );
});

test('cijfers-pagina rekent jaaromzet en maandgrafiek correct', function () {
    $admin = adminUser();
    $customer = approvedCustomer();
    $january = \Illuminate\Support\Carbon::create(now()->year, 1, 15, 10);

    \App\Models\Order::factory()->confirmed()->create([
        'customer_id' => $customer->id,
        'total' => 100,
        'created_at' => $january,
    ]);
    \App\Models\Order::factory()->confirmed()->create([
        'customer_id' => $customer->id,
        'total' => 50,
        'created_at' => $january,
    ]);

    $this->actingAs($admin)
        ->get('/admin/cijfers')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.currentYearRevenue', '150.00')
            ->where('stats.ordersThisYear', 2)
            ->where('chart.0.label', 'jan')
            ->where('chart.0.orders', 2)
            ->where('chart.0.revenue', 150)
        );
});

test('niet-admin heeft geen toegang tot de cijfers-pagina', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->get('/admin/cijfers')
        ->assertForbidden();
});
