<?php

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
    // Telefoonnummer met streepje-spatie-prefix (geen Excel-apostrof)
    expect($csv)->toContain('- 0612345678');
    expect($csv)->not->toContain("'0612345678");
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
