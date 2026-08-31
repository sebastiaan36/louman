<?php

use App\Http\Controllers\Admin\DeliveryRouteController;
use App\Models\Customer;

function csvFromExport($response): string
{
    ob_start();
    $response->sendContent();

    return ob_get_clean();
}

test('rijroute-export bevat klantnaam, telefoon, leverdag en volgnummer voor een dag', function () {
    $admin = adminUser();
    Customer::factory()->approved()->create([
        'company_name' => 'Bakker Jan',
        'phone_number' => '0612345678',
        'delivery_day' => 'maandag',
        'route_order' => 1,
    ]);
    // Andere dag: mag niet in de maandag-export
    Customer::factory()->approved()->create([
        'company_name' => 'Slager Piet',
        'delivery_day' => 'dinsdag',
        'route_order' => 1,
    ]);

    $response = $this->actingAs($admin)->get('/admin/delivery-route/export?day=maandag');
    $response->assertOk();

    $csv = csvFromExport($response);
    expect($csv)->toContain('klantnaam;telefoonnummer;leverdag;rijroute');
    expect($csv)->toContain('Bakker Jan');
    expect($csv)->toContain('maandag');
    expect($csv)->not->toContain('Slager Piet');
    // Het telefoonnummer staat er precies zo in als het is opgeslagen.
    expect($csv)->toContain('0612345678');
});

test('het telefoonnummer krijgt geen streepje meer voor zich', function () {
    $admin = adminUser();
    Customer::factory()->approved()->create([
        'company_name' => 'Bakker Jan',
        'phone_number' => '020-6249065',
        'delivery_day' => 'maandag',
        'route_order' => 1,
    ]);

    $csv = csvFromExport($this->actingAs($admin)->get('/admin/delivery-route/export?day=maandag'));

    // Excel las "- 020-6249065" als een aftreksom en maakte er -6249085 van.
    expect($csv)->toContain(';020-6249065;')
        ->and($csv)->not->toContain('- 020-6249065')
        ->and($csv)->not->toContain('-6249085');
});

test('een waarde die als formule gelezen zou worden wordt als tekst gemarkeerd', function () {
    $admin = adminUser();
    Customer::factory()->approved()->create([
        'company_name' => '=Kwaad BV',
        'phone_number' => '+31206249065',
        'delivery_day' => 'maandag',
        'route_order' => 1,
    ]);

    $csv = csvFromExport($this->actingAs($admin)->get('/admin/delivery-route/export?day=maandag'));

    expect($csv)->toContain("'=Kwaad BV")
        ->and($csv)->toContain("'+31206249065");
});

test('rijroute-export zonder geldige dag bevat alle dagen', function () {
    $admin = adminUser();
    Customer::factory()->approved()->create(['company_name' => 'Bakker Jan', 'delivery_day' => 'maandag', 'route_order' => 1]);
    Customer::factory()->approved()->create(['company_name' => 'Slager Piet', 'delivery_day' => 'dinsdag', 'route_order' => 1]);

    $response = $this->actingAs($admin)->get('/admin/delivery-route/export?day=all');
    $response->assertOk();

    $csv = csvFromExport($response);
    expect($csv)->toContain('Bakker Jan');
    expect($csv)->toContain('Slager Piet');
});

test('rijroute-export neemt klanten zonder telefoonnummer mee met leeg veld', function () {
    $admin = adminUser();
    Customer::factory()->approved()->create([
        'company_name' => 'Geen Telefoon BV',
        'phone_number' => null,
        'delivery_day' => 'maandag',
        'route_order' => 2,
    ]);

    $response = $this->actingAs($admin)->get('/admin/delivery-route/export?day=maandag');
    $csv = csvFromExport($response);

    expect($csv)->toContain('Geen Telefoon BV');
    // Lege telefoonkolom (twee opeenvolgende scheidingstekens), dan dag en volgnummer.
    expect($csv)->toContain(';;maandag;2');
});

test('zaterdag en zondag zijn geen bezorgdagen meer', function () {
    expect(DeliveryRouteController::DAYS)
        ->not->toContain('zaterdag')
        ->not->toContain('zondag')
        ->toContain('maandag')
        ->toContain('vrijdag')
        ->toContain('ophalen');
});

test('de rijroute-pagina biedt zaterdag en zondag niet meer aan', function () {
    $this->actingAs(adminUser())
        ->get('/admin/delivery-route')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/DeliveryRoute')
            ->where('days', DeliveryRouteController::DAYS)
        );
});

test('de export van alle dagen bevat elke bezorgdag', function () {
    $admin = adminUser();

    foreach (['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'ophalen'] as $index => $day) {
        Customer::factory()->approved()->create([
            'company_name' => 'Klant '.$day,
            'delivery_day' => $day,
            'route_order' => $index + 1,
        ]);
    }

    $csv = csvFromExport($this->actingAs($admin)->get('/admin/delivery-route/export?day=all'));

    foreach (['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'ophalen'] as $day) {
        expect($csv)->toContain('Klant '.$day);
    }
});

test('een klant die nog op zaterdag staat verdwijnt niet uit de export van alle dagen', function () {
    $admin = adminUser();
    Customer::factory()->approved()->create([
        'company_name' => 'Weekendklant BV',
        'delivery_day' => 'zaterdag',
        'route_order' => 1,
    ]);

    $csv = csvFromExport($this->actingAs($admin)->get('/admin/delivery-route/export?day=all'));

    // Zaterdag is als bezorgdag vervallen, maar een klant die er nog op staat
    // mag niet stilletjes uit de lijst vallen; die komt achteraan te staan.
    expect($csv)->toContain('Weekendklant BV');
});

test('niet-admin heeft geen toegang tot de rijroute-export', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->get('/admin/delivery-route/export?day=maandag')
        ->assertForbidden();
});

test('rijroute-volgorde opslaan geeft een inertia-redirect terug, geen json', function () {
    $admin = adminUser();
    $a = Customer::factory()->approved()->create(['delivery_day' => 'maandag', 'route_order' => 1]);
    $b = Customer::factory()->approved()->create(['delivery_day' => 'maandag', 'route_order' => 2]);

    $this->actingAs($admin)
        ->post('/admin/delivery-route/order', [
            'day' => 'maandag',
            'order' => [$b->id, $a->id],
        ])
        ->assertRedirect();

    expect($b->fresh()->route_order)->toBe(1);
    expect($a->fresh()->route_order)->toBe(2);
});
