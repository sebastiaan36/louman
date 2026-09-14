<?php

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Carbon;

test('de rijroute toont of een klant een nog niet voltooide bestelling heeft', function () {
    $withOpen = Customer::factory()->approved()->create(['company_name' => 'Aap', 'delivery_day' => 'maandag', 'route_order' => 1]);
    $withoutOpen = Customer::factory()->approved()->create(['company_name' => 'Beer', 'delivery_day' => 'maandag', 'route_order' => 2]);

    Order::factory()->pending()->create(['customer_id' => $withOpen->id]);
    Order::factory()->confirmed()->create(['customer_id' => $withOpen->id]);
    Order::factory()->completed()->create(['customer_id' => $withOpen->id]);
    Order::factory()->completed()->create(['customer_id' => $withoutOpen->id]);
    Order::factory()->cancelled()->create(['customer_id' => $withoutOpen->id]);

    $this->actingAs(adminUser())
        ->get('/admin/delivery-route?day=maandag')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('customers.0.open_orders_count', 2)
            ->where('customers.1.open_orders_count', 0)
            ->missing('customers.0.route_flag_week')
        );
});

test('een beheerder kan aangeven dat een klant teruggebeld wil worden', function () {
    $customer = Customer::factory()->approved()->create(['delivery_day' => 'maandag']);

    $this->actingAs(adminUser())
        ->from('/admin/delivery-route?day=maandag')
        ->patch("/admin/delivery-route/{$customer->id}/flag", ['flag' => 'callback'])
        ->assertRedirect('/admin/delivery-route?day=maandag');

    expect($customer->fresh()->activeRouteFlag())->toBe('callback');

    $this->actingAs(adminUser())
        ->get('/admin/delivery-route?day=maandag')
        ->assertInertia(fn ($page) => $page->where('customers.0.route_flag', 'callback'));
});

test('de markering kan worden gewisseld en gewist', function () {
    $customer = Customer::factory()->approved()->create();
    $admin = adminUser();

    $this->actingAs($admin)->patch("/admin/delivery-route/{$customer->id}/flag", ['flag' => 'skip_week']);
    expect($customer->fresh()->activeRouteFlag())->toBe('skip_week');

    $this->actingAs($admin)->patch("/admin/delivery-route/{$customer->id}/flag", ['flag' => null]);
    expect($customer->fresh()->activeRouteFlag())->toBeNull()
        ->and($customer->fresh()->route_flag_week)->toBeNull();
});

test('een onbekende markering wordt geweigerd', function () {
    $customer = Customer::factory()->approved()->create();

    $this->actingAs(adminUser())
        ->from('/admin/delivery-route')
        ->patch("/admin/delivery-route/{$customer->id}/flag", ['flag' => 'vakantie'])
        ->assertSessionHasErrors('flag');
});

test('een markering vervalt op zondagavond om 21:00 Nederlandse tijd', function () {
    // Woensdag 16 september 2026, midden in de week.
    Carbon::setTestNow('2026-09-16 10:00:00');
    $customer = Customer::factory()->approved()->create(['delivery_day' => 'woensdag']);
    $customer->setRouteFlag('skip_week');
    expect($customer->fresh()->activeRouteFlag())->toBe('skip_week');

    // Zondag 20 september 20:59 Amsterdam (18:59 UTC, zomertijd): nog actief.
    Carbon::setTestNow('2026-09-20 18:59:00');
    expect($customer->fresh()->activeRouteFlag())->toBe('skip_week');

    // Zondag 20 september 21:00 Amsterdam (19:00 UTC): vervallen.
    Carbon::setTestNow('2026-09-20 19:00:00');
    expect($customer->fresh()->activeRouteFlag())->toBeNull();

    // Een markering die zondagavond na 21:00 wordt gezet, hoort bij de nieuwe week.
    Carbon::setTestNow('2026-09-20 20:00:00');
    $customer->setRouteFlag('callback');
    Carbon::setTestNow('2026-09-23 08:00:00');
    expect($customer->fresh()->activeRouteFlag())->toBe('callback');

    Carbon::setTestNow('2026-09-21 06:00:00');
    $customer->setRouteFlag(null);
    expect($customer->fresh()->activeRouteFlag())->toBeNull();

    $this->actingAs(adminUser())
        ->get('/admin/delivery-route?day=woensdag')
        ->assertInertia(fn ($page) => $page->where('customers.0.route_flag', null));

    Carbon::setTestNow();
});

test('de weergave van alle dagen bevat dezelfde gegevens', function () {
    $customer = Customer::factory()->approved()->create(['delivery_day' => 'dinsdag']);
    $customer->setRouteFlag('callback');
    Order::factory()->pending()->create(['customer_id' => $customer->id]);

    $this->actingAs(adminUser())
        ->get('/admin/delivery-route?day=all')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('dayGroups.1.day', 'dinsdag')
            ->where('dayGroups.1.customers.0.open_orders_count', 1)
            ->where('dayGroups.1.customers.0.route_flag', 'callback')
        );
});

test('klanten mogen geen markering zetten', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->patch("/admin/delivery-route/{$customer->id}/flag", ['flag' => 'callback'])
        ->assertForbidden();
});

test('beide weergaven gebruiken het gedeelde actiecomponent', function () {
    $root = dirname(__DIR__, 3);
    $page = file_get_contents($root.'/resources/js/pages/admin/DeliveryRoute.vue');
    $component = file_get_contents($root.'/resources/js/components/DeliveryRouteCustomerActions.vue');

    expect(substr_count($page, '<DeliveryRouteCustomerActions'))->toBe(2);
    expect($component)
        ->toContain('Terugbellen')
        ->toContain('Niet deze week')
        ->toContain('Bestelling maken')
        ->toContain('Heeft besteld')
        // Kleuren: besteld groen, terugbellen oranje, niet deze week rood.
        ->toContain('bg-emerald-600')
        ->toContain("routeFlag === 'callback' ? 'border-orange-500 bg-orange-500")
        ->toContain("routeFlag === 'skip_week' ? 'border-red-600 bg-red-600");
});
