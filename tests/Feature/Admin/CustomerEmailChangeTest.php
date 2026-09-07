<?php

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Setting;
use App\Notifications\CustomerEmailChanged;
use App\Notifications\EmailAddressChanged;
use Illuminate\Support\Facades\Notification;

function customerWithAccount(string $email = 'oud@klant.nl'): Customer
{
    $user = customerUser();
    $user->forceFill(['email' => $email])->save();

    return Customer::factory()->approved()->create([
        'user_id' => $user->id,
        'company_name' => 'Bakkerij De Hoek',
    ]);
}

test('de admin kan het e-mailadres van een klant wijzigen', function () {
    Notification::fake();

    $customer = customerWithAccount('oud@klant.nl');

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/email", ['email' => 'nieuw@klant.nl'])
        ->assertRedirect()
        ->assertSessionHas('success', 'E-mailadres gewijzigd naar nieuw@klant.nl.');

    expect($customer->fresh()->user->email)->toBe('nieuw@klant.nl');
});

test('de klant krijgt bericht op het oude en het nieuwe adres', function () {
    Notification::fake();

    $customer = customerWithAccount('oud@klant.nl');

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/email", ['email' => 'nieuw@klant.nl']);

    $ontvangers = [];

    Notification::assertSentOnDemand(
        EmailAddressChanged::class,
        function ($notification, $channels, $notifiable) use (&$ontvangers) {
            $ontvangers = array_merge($ontvangers, (array) $notifiable->routes['mail']);

            return true;
        },
    );

    expect($ontvangers)->toContain('nieuw@klant.nl')->toContain('oud@klant.nl');
});

test('de beheerders krijgen een kopie', function () {
    Notification::fake();

    $admin = adminUser();
    $customer = customerWithAccount('oud@klant.nl');

    $this->actingAs($admin)
        ->patch("/admin/customers/{$customer->id}/email", ['email' => 'nieuw@klant.nl']);

    Notification::assertSentTo(
        $admin,
        CustomerEmailChanged::class,
        fn (CustomerEmailChanged $n) => $n->previousEmail === 'oud@klant.nl'
            && $n->newEmail === 'nieuw@klant.nl',
    );
});

test('de kopie gaat naar het ingestelde meldingsadres als dat gevuld is', function () {
    Notification::fake();

    Setting::set(Setting::MAIL_REGISTRATION_NOTIFICATION, 'meldingen@louman.nl');
    adminUser();
    $customer = customerWithAccount();

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/email", ['email' => 'nieuw@klant.nl']);

    Notification::assertSentOnDemand(
        CustomerEmailChanged::class,
        fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'meldingen@louman.nl',
    );
});

test('een adres dat al in gebruik is wordt geweigerd', function () {
    Notification::fake();

    $bezet = customerUser();
    $bezet->forceFill(['email' => 'bezet@klant.nl'])->save();

    $customer = customerWithAccount('oud@klant.nl');

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/email", ['email' => 'bezet@klant.nl'])
        ->assertSessionHasErrors(['email' => 'Dit e-mailadres is al in gebruik.']);

    expect($customer->fresh()->user->email)->toBe('oud@klant.nl');
    Notification::assertNothingSent();
});

test('hetzelfde adres opnieuw opslaan verandert niets en stuurt geen mail', function () {
    Notification::fake();

    $customer = customerWithAccount('zelfde@klant.nl');

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/email", ['email' => 'zelfde@klant.nl'])
        ->assertSessionHas('error', 'Dit is al het huidige e-mailadres.');

    Notification::assertNothingSent();
});

test('een ongeldig adres wordt geweigerd', function () {
    $customer = customerWithAccount();

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/email", ['email' => 'geen-adres'])
        ->assertSessionHasErrors(['email' => 'Vul een geldig e-mailadres in.']);
});

test('een klant zonder account kan geen adres krijgen via dit scherm', function () {
    Notification::fake();

    $customer = Customer::factory()->approved()->create(['user_id' => null]);

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/email", ['email' => 'nieuw@klant.nl'])
        ->assertSessionHas('error', 'Deze klant heeft nog geen account. Stuur eerst een uitnodiging.');

    Notification::assertNothingSent();
});

test('de wijziging wordt vastgelegd in het auditlogboek', function () {
    Notification::fake();

    $customer = customerWithAccount('oud@klant.nl');

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/email", ['email' => 'nieuw@klant.nl']);

    $log = AuditLog::where('action', 'customer.email_changed')->firstOrFail();

    expect($log->metadata['previous_email'])->toBe('oud@klant.nl')
        ->and($log->metadata['new_email'])->toBe('nieuw@klant.nl');
});

test('een niet-admin kan geen e-mailadres wijzigen', function () {
    $customer = customerWithAccount();

    $this->actingAs(approvedCustomer()->user)
        ->patch("/admin/customers/{$customer->id}/email", ['email' => 'nieuw@klant.nl'])
        ->assertForbidden();
});
