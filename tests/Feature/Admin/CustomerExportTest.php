<?php

use App\Models\Customer;

function customerCsv($response): string
{
    ob_start();
    $response->sendContent();

    return ob_get_clean();
}

test('de klantexport bevat het klantnummer', function () {
    Customer::factory()->approved()->create([
        'company_name' => "'T Beste",
        'customer_number' => '133',
    ]);

    $csv = customerCsv($this->actingAs(adminUser())->get('/admin/customers/export'));

    expect($csv)
        ->toContain('id;customer_number;company_name')
        ->toContain(';133;"\'T Beste";');
});

test('een klant zonder klantnummer levert een lege kolom op', function () {
    Customer::factory()->approved()->create([
        'company_name' => 'Zonder Nummer BV',
        'customer_number' => null,
    ]);

    $csv = customerCsv($this->actingAs(adminUser())->get('/admin/customers/export'));

    expect($csv)->toContain(';;"Zonder Nummer BV";');
});

test('de id blijft in de export staan, want de import zoekt daarop', function () {
    $customer = Customer::factory()->approved()->create(['customer_number' => '133']);

    $csv = customerCsv($this->actingAs(adminUser())->get('/admin/customers/export'));

    expect($csv)->toContain("\n{$customer->id};133;");
});

test('de import negeert de klantnummer-kolom', function () {
    $customer = Customer::factory()->approved()->create([
        'company_name' => 'Oude Naam',
        'customer_number' => '133',
    ]);

    $csv = "id;customer_number;company_name\n{$customer->id};999;Nieuwe Naam\n";
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('klanten.csv', $csv);

    $this->actingAs(adminUser())
        ->post('/admin/customers/import', ['csv_file' => $file])
        ->assertRedirect();

    // De naam is bijgewerkt, het klantnummer bewust niet.
    expect($customer->fresh())
        ->company_name->toBe('Nieuwe Naam')
        ->customer_number->toBe('133');
});
