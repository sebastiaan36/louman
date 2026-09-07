<?php

use App\Models\Customer;

function registratie(array $overrides = []): array
{
    return array_merge([
        'company_name' => 'Bakkerij De Hoek',
        'contact_person' => 'Sam de Vries',
        'email' => 'sam@dehoek.test',
        'password' => 'Wachtwoord!2026',
        'password_confirmation' => 'Wachtwoord!2026',
        'phone_number' => '0301234567',
        'street_name' => 'Hoofdstraat',
        'house_number' => '12',
        'postal_code' => '1234 AB',
        'city' => 'Utrecht',
        'kvk_number' => '12345678',
        'bank_account' => 'NL91ABNA0417164300',
        'vat_number' => 'NL123456789B01',
        'terms_accepted' => true,
    ], $overrides);
}

test('een BTW-nummer zonder NL levert een melding op die zegt wat eraan moet', function () {
    $this->post(route('customer.register.store'), registratie(['vat_number' => '123456789B01']))
        ->assertSessionHasErrors([
            'vat_number' => 'Het BTW-nummer moet met NL beginnen. Vul NL123456789B01 in.',
        ]);
});

test('een te kort KvK-nummer noemt hoeveel cijfers er zijn ingevuld', function () {
    $this->post(route('customer.register.store'), registratie(['kvk_number' => '123456']))
        ->assertSessionHasErrors([
            'kvk_number' => 'Het KvK-nummer bestaat uit 8 cijfers, u vulde er 6 in. Bijvoorbeeld 12345678.',
        ]);
});

test('een buitenlands rekeningnummer legt uit dat alleen NL kan', function () {
    $this->post(route('customer.register.store'), registratie(['bank_account' => 'BE68539007547034']))
        ->assertSessionHasErrors('bank_account');

    expect(session('errors')->first('bank_account'))->toContain('moet met NL beginnen');
});

test('een IBAN met spaties wordt geaccepteerd en zonder spaties opgeslagen', function () {
    $this->post(route('customer.register.store'), registratie([
        'bank_account' => 'NL91 ABNA 0417 1643 00',
        'vat_number' => 'nl123456789b01',
        'kvk_number' => '1234 5678',
        'postal_code' => '1234ab',
    ]))->assertSessionHasNoErrors();

    $customer = Customer::where('company_name', 'Bakkerij De Hoek')->firstOrFail();

    expect($customer)
        ->bank_account->toBe('NL91ABNA0417164300')
        ->vat_number->toBe('NL123456789B01')
        ->kvk_number->toBe('12345678')
        ->postal_code->toBe('1234 AB');
});

test('een postcode zonder letters vraagt om de letters', function () {
    $this->post(route('customer.register.store'), registratie(['postal_code' => '1234']))
        ->assertSessionHasErrors([
            'postal_code' => 'Vul ook de twee letters van de postcode in, bijvoorbeeld 1234 AB.',
        ]);
});

test('een te kort telefoonnummer noemt het aantal cijfers', function () {
    $this->post(route('customer.register.store'), registratie(['phone_number' => '06123456']))
        ->assertSessionHasErrors('phone_number');

    expect(session('errors')->first('phone_number'))->toContain('u vulde er 8 in');
});
