<?php

use App\Models\Customer;
use App\Models\CustomerInvitation;

function createInvitation(string $email = 'klant@example.com', ?\DateTimeInterface $expiresAt = null, ?\DateTimeInterface $acceptedAt = null): array
{
    $customer = Customer::factory()->create(['user_id' => null, 'approved_at' => now()]);
    $rawToken = 'raw-token-'.uniqid();

    $invitation = CustomerInvitation::create([
        'customer_id' => $customer->id,
        'email' => $email,
        'token' => hash('sha256', $rawToken),
        'expires_at' => $expiresAt ?? now()->addDays(30),
        'accepted_at' => $acceptedAt,
    ]);

    return [$invitation, $rawToken];
}

test('show toont accept-invitation pagina voor geldig token', function () {
    [, $rawToken] = createInvitation('test@example.com');

    $this->get("/invitation/{$rawToken}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/AcceptInvitation')
            ->where('email', 'test@example.com')
        );
});

test('show geeft 404 bij ongeldig token', function () {
    $this->get('/invitation/onbestaand-token')->assertNotFound();
});

test('show geeft 404 bij verlopen token', function () {
    [, $rawToken] = createInvitation(expiresAt: now()->subDay());

    $this->get("/invitation/{$rawToken}")->assertNotFound();
});

test('show geeft 404 bij reeds geaccepteerd token', function () {
    [, $rawToken] = createInvitation(acceptedAt: now());

    $this->get("/invitation/{$rawToken}")->assertNotFound();
});

test('accept maakt user aan, koppelt aan klant, en logt in', function () {
    [$invitation, $rawToken] = createInvitation('nieuw@example.com');

    $response = $this->post("/invitation/{$rawToken}", [
        'password' => 'GeheimWachtwoord123!',
        'password_confirmation' => 'GeheimWachtwoord123!',
    ]);

    $response->assertRedirect(route('customer.complete-profile.edit'));

    $user = \App\Models\User::where('email', 'nieuw@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('customer');
    expect($user->email_verified_at)->not->toBeNull();
    expect(\Illuminate\Support\Facades\Hash::check('GeheimWachtwoord123!', $user->password))->toBeTrue();

    expect($invitation->customer->fresh()->user_id)->toBe($user->id);
    expect($invitation->fresh()->accepted_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('accept faalt bij wachtwoord-bevestiging mismatch', function () {
    [, $rawToken] = createInvitation();

    $this->post("/invitation/{$rawToken}", [
        'password' => 'GeheimWachtwoord123!',
        'password_confirmation' => 'iets anders',
    ])->assertSessionHasErrors('password');
});

test('accept toont nederlandse melding bij te kort wachtwoord onder strikte regels', function () {
    \Illuminate\Validation\Rules\Password::defaults(
        fn () => \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()
    );

    [, $rawToken] = createInvitation();

    $this->post("/invitation/{$rawToken}", [
        'password' => 'Kort1!',
        'password_confirmation' => 'Kort1!',
    ])->assertSessionHasErrors([
        'password' => 'Het wachtwoord moet minimaal 12 tekens bevatten.',
    ]);
});

test('accept toont nederlandse melding bij ontbrekend symbool onder strikte regels', function () {
    \Illuminate\Validation\Rules\Password::defaults(
        fn () => \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()
    );

    [, $rawToken] = createInvitation();

    $this->post("/invitation/{$rawToken}", [
        'password' => 'GeenSymbool1234',
        'password_confirmation' => 'GeenSymbool1234',
    ])->assertSessionHasErrors([
        'password' => 'Gebruik minimaal één symbool (bijv. ! @ # $).',
    ]);
});

test('complete-profile redirect naar dashboard als profiel al compleet is', function () {
    $customer = Customer::factory()->approved()->create();

    $this->actingAs($customer->user)
        ->get('/customer/complete-profile')
        ->assertRedirect(route('dashboard'));
});

test('complete-profile toont formulier als profiel onvolledig is', function () {
    $user = customerUser();
    $customer = Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'contact_person' => null,
        'phone_number' => null,
    ]);

    $this->actingAs($user)
        ->get('/customer/complete-profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customer/CompleteProfile')
            ->where('customer.company_name', $customer->company_name)
        );
});

test('complete-profile slaat gegevens op', function () {
    $user = customerUser();
    Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'contact_person' => null,
        'phone_number' => null,
        'street_name' => null,
        'house_number' => null,
        'postal_code' => null,
        'city' => null,
        'kvk_number' => null,
        'bank_account' => null,
        'vat_number' => null,
    ]);

    $this->actingAs($user)
        ->patch('/customer/complete-profile', [
            'contact_person' => 'Piet Klant',
            'phone_number' => '06-12345678',
            'street_name' => 'Hoofdstraat',
            'house_number' => '12',
            'postal_code' => '1234 AB',
            'city' => 'Amsterdam',
            'kvk_number' => '12345678',
            'bank_account' => 'NL91ABNA0417164300',
            'vat_number' => 'NL123456789B01',
        ])
        ->assertRedirect(route('dashboard'));

    $customer = $user->fresh()->customer;
    expect($customer->contact_person)->toBe('Piet Klant');
    expect($customer->kvk_number)->toBe('12345678');
});

test('customer met incompleet profiel wordt vanuit customer-area doorverwezen', function () {
    $user = customerUser();
    Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'contact_person' => null,
    ]);

    $this->actingAs($user)
        ->get('/customer/products')
        ->assertRedirect(route('customer.complete-profile.edit'));
});
