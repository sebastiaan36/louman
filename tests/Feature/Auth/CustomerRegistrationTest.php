<?php

use App\Models\Customer;
use App\Models\User;

/**
 * @return array<string, mixed>
 */
function validCustomerRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'company_name' => 'Worstmakerij Test',
        'contact_person' => 'Jan Jansen',
        'email' => 'jan@test.nl',
        'phone_number' => '06-12345678',
        'street_name' => 'Teststraat',
        'house_number' => '12',
        'postal_code' => '1234 AB',
        'city' => 'Amsterdam',
        'kvk_number' => '12345678',
        'bank_account' => 'NL91ABNA0417164300',
        'vat_number' => 'NL123456789B01',
        'password' => 'Wachtwoord123!',
        'password_confirmation' => 'Wachtwoord123!',
        'show_on_map' => '1',
        'terms_accepted' => '1',
    ], $overrides);
}

test('klant kan registreren met akkoord op de voorwaarden', function () {
    $this->post('/register/customer', validCustomerRegistrationPayload())
        ->assertRedirect(route('customer.awaiting-approval'));

    $customer = Customer::first();
    expect($customer)->not->toBeNull();
    expect($customer->terms_accepted_at)->not->toBeNull();
    expect(User::where('email', 'jan@test.nl')->exists())->toBeTrue();
});

test('registratie wordt geblokkeerd zonder akkoord op de voorwaarden', function () {
    $this->post('/register/customer', validCustomerRegistrationPayload(['terms_accepted' => '0']))
        ->assertSessionHasErrors('terms_accepted');

    expect(Customer::count())->toBe(0);
    expect(User::where('email', 'jan@test.nl')->exists())->toBeFalse();
});

test('kaartvoorkeur wordt opgeslagen zoals aangeleverd', function () {
    $this->post('/register/customer', validCustomerRegistrationPayload([
        'show_on_map' => '0',
        'email' => 'geenkaart@test.nl',
        'kvk_number' => '87654321',
    ]))->assertRedirect(route('customer.awaiting-approval'));

    $customer = Customer::first();
    expect($customer->show_on_map)->toBeFalse();
});

test('terms pagina is publiek bereikbaar', function () {
    $this->get('/algemene-voorwaarden')->assertOk();
});
