<?php

use App\Models\AuditLog;
use App\Models\Customer;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

function klantMetHalfProfiel(): Customer
{
    $user = customerUser();
    $user->forceFill(['email' => 'klant@bedrijf.nl'])->save();

    return Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'company_name' => 'Koffiehuis De Markt',
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
}

test('de klantpagina benoemt welke profielvelden nog ontbreken', function () {
    $customer = klantMetHalfProfiel();

    $this->actingAs(adminUser())
        ->get("/admin/customers/{$customer->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('customer.missing_profile_fields', array_values(Customer::PROFILE_FIELDS))
        );
});

test('een afgerond profiel levert een lege lijst op', function () {
    $customer = Customer::factory()->approved()->create(['user_id' => customerUser()->id]);

    $this->actingAs(adminUser())
        ->get("/admin/customers/{$customer->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('customer.missing_profile_fields', []));
});

test('een klant zonder account krijgt geen ontbrekende velden te zien', function () {
    $customer = Customer::factory()->approved()->create(['user_id' => null, 'contact_person' => null]);

    $this->actingAs(adminUser())
        ->get("/admin/customers/{$customer->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('customer.missing_profile_fields', []));
});

test('de middleware en het scherm gebruiken dezelfde veldenlijst', function () {
    $customer = klantMetHalfProfiel();

    expect($customer->hasCompleteProfile())->toBeFalse();

    // Zolang het profiel niet af is, stuurt het portaal de klant naar het formulier.
    $this->actingAs($customer->user)
        ->get('/customer/products')
        ->assertRedirect(route('customer.complete-profile.edit'));
});

test('de beheerder kan een herstelmail sturen', function () {
    Notification::fake();

    $customer = klantMetHalfProfiel();

    $this->actingAs(adminUser())
        ->post("/admin/customers/{$customer->id}/password-reset")
        ->assertRedirect()
        ->assertSessionHas('success', 'Herstelmail verstuurd naar klant@bedrijf.nl.');

    Notification::assertSentTo($customer->user, ResetPasswordNotification::class);
});

test('de herstelmail wordt vastgelegd in het auditlogboek', function () {
    Notification::fake();

    $customer = klantMetHalfProfiel();

    $this->actingAs(adminUser())->post("/admin/customers/{$customer->id}/password-reset");

    expect(AuditLog::where('action', 'customer.password_reset_sent')->exists())->toBeTrue();
});

test('een klant zonder account krijgt geen herstelmail', function () {
    Notification::fake();

    $customer = Customer::factory()->approved()->create(['user_id' => null]);

    $this->actingAs(adminUser())
        ->post("/admin/customers/{$customer->id}/password-reset")
        ->assertSessionHas('error', 'Deze klant heeft nog geen account. Stuur eerst een uitnodiging.');

    Notification::assertNothingSent();
});

test('een niet-admin kan geen herstelmail sturen', function () {
    $customer = klantMetHalfProfiel();

    $this->actingAs(approvedCustomer()->user)
        ->post("/admin/customers/{$customer->id}/password-reset")
        ->assertForbidden();
});
