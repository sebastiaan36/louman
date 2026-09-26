<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\MpdfRenderer;

function captureOverviewLayout(): object
{
    $holder = new stdClass;
    $holder->data = null;
    $holder->filename = null;

    $mock = Mockery::mock(MpdfRenderer::class);
    $mock->shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function ($view, $data) use ($holder, $mock) {
            $holder->data = $data;

            return $mock;
        });
    $mock->shouldReceive('stream')->andReturnUsing(function ($filename) use ($holder) {
        $holder->filename = $filename;

        return response('', 200);
    });

    app()->instance(MpdfRenderer::class, $mock);

    return $holder;
}

function customersOnDifferentDays(): void
{
    $product = Product::factory()->create();

    foreach ([
        ['Zuivelhuis', 'maandag', 1],
        ['bakkerij Aarts', 'vrijdag', 1],
        ['Koffiehuis', 'maandag', 2],
        ['Nieuwe klant', null, null],
    ] as [$name, $day, $routeOrder]) {
        $customer = Customer::factory()->approved()->create([
            'company_name' => $name,
            'delivery_day' => $day,
            'route_order' => $routeOrder,
        ]);
        $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1]);
    }
}

test('het bestellingenoverzicht op de bestellingenpagina zet alle klanten op alfabet met hun leverdag', function () {
    customersOnDifferentDays();
    $overview = captureOverviewLayout();

    $this->actingAs(adminUser())
        ->get('/admin/orders/customer-overview')
        ->assertOk();

    expect(array_keys($overview->data['dayGroups']))->toBe(['alle'])
        ->and($overview->data['groupTitles']['alle'])->toBe('Alle klanten A–Z')
        ->and($overview->data['title'])->toBe('Bestellingenoverzicht');

    $cards = $overview->data['dayGroups']['alle'];

    expect(array_column($cards, 'company_name'))->toBe(['bakkerij Aarts', 'Koffiehuis', 'Nieuwe klant', 'Zuivelhuis'])
        ->and(array_column($cards, 'delivery_day'))->toBe(['Vrijdag', 'Maandag', 'Niet bekend', 'Maandag']);
});

test('het bestellingenoverzicht per dag staat onder de rijroute en groepeert op leverdag in routevolgorde', function () {
    customersOnDifferentDays();
    $overview = captureOverviewLayout();

    $this->actingAs(adminUser())
        ->get('/admin/delivery-route/orders-overview')
        ->assertOk();

    $groups = $overview->data['dayGroups'];

    expect(array_keys($groups))->toBe(['maandag', 'vrijdag', 'onbekend'])
        ->and(array_column($groups['maandag'], 'company_name'))->toBe(['Zuivelhuis', 'Koffiehuis'])
        ->and($groups['maandag'][0]['delivery_day'])->toBeNull()
        ->and($overview->data['title'])->toBe('Bestellingenoverzicht per dag')
        ->and($overview->filename)->toStartWith('bestellingenoverzicht-per-dag-');
});

test('het bestellingenoverzicht per dag is alleen voor beheerders', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->get('/admin/delivery-route/orders-overview')
        ->assertForbidden();
});

test('de klantkaart toont de leverdag alleen als die is meegegeven', function () {
    $card = fn (?string $day) => view('pdf.partials.customer-card', ['customer' => [
        'company_name' => 'Koffiehuis',
        'number' => '12',
        'phone_number' => null,
        'is_pickup' => false,
        'delivery_day' => $day,
        'products' => [],
        'notes' => [],
    ]])->render();

    expect($card('Maandag'))->toContain('Leverdag <strong>Maandag</strong>')
        ->and($card(null))->not->toContain('Leverdag');
});

test('de overzichten gebruiken zwarte tekst en de knoppen staan op de juiste pagina', function () {
    $root = dirname(__DIR__, 3);
    $css = file_get_contents($root.'/resources/views/pdf/partials/overview-css.blade.php');

    preg_match_all('/(?<!-)color:\s*(#[0-9a-fA-F]{3,6})/', $css, $colors);

    // Alleen zwart, plus wit voor de tekst op het donkere Ophalen-label.
    $unique = array_values(array_unique($colors[1]));
    sort($unique);

    expect($unique)->toBe(['#000', '#fff']);

    expect(file_get_contents($root.'/resources/js/pages/admin/DeliveryRoute.vue'))
        ->toContain('href="/admin/delivery-route/orders-overview"')
        ->toContain('Bestellingenoverzicht per dag');
    expect(file_get_contents($root.'/resources/js/pages/admin/Orders.vue'))
        ->toContain('href="/admin/orders/customer-overview"')
        ->not->toContain('Bestellingenoverzicht per dag');
});
