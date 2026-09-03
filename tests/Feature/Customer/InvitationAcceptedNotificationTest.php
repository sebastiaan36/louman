<?php

use App\Models\Customer;
use App\Models\CustomerInvitation;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CustomerAcceptedInvitation;
use Illuminate\Support\Facades\Notification;

function invitationFor(string $email = 'klant@example.com'): array
{
    $customer = Customer::factory()->create([
        'user_id' => null,
        'approved_at' => now(),
        'company_name' => 'Bakkerij De Hoek',
    ]);

    $rawToken = 'raw-token-'.uniqid();

    CustomerInvitation::create([
        'customer_id' => $customer->id,
        'email' => $email,
        'token' => hash('sha256', $rawToken),
        'expires_at' => now()->addDays(30),
    ]);

    return [$customer, $rawToken];
}

test('beheerders krijgen bericht als een genodigde klant een account aanmaakt', function () {
    Notification::fake();

    [$customer, $rawToken] = invitationFor('nieuw@klant.nl');
    $admin = adminUser();

    $this->post("/invitation/{$rawToken}", [
        'password' => 'Wachtwoord!2026',
        'password_confirmation' => 'Wachtwoord!2026',
    ])->assertRedirect();

    Notification::assertSentTo(
        $admin,
        CustomerAcceptedInvitation::class,
        fn (CustomerAcceptedInvitation $n) => $n->customer->is($customer),
    );
});

test('het bericht gaat naar het ingestelde meldingsadres als dat gevuld is', function () {
    Notification::fake();

    Setting::set(Setting::MAIL_REGISTRATION_NOTIFICATION, 'meldingen@louman.nl');
    adminUser();

    [, $rawToken] = invitationFor();

    $this->post("/invitation/{$rawToken}", [
        'password' => 'Wachtwoord!2026',
        'password_confirmation' => 'Wachtwoord!2026',
    ])->assertRedirect();

    Notification::assertSentOnDemand(
        CustomerAcceptedInvitation::class,
        fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'meldingen@louman.nl',
    );
});

test('het bericht bevat de bedrijfsnaam en het e-mailadres van de klant', function () {
    [$customer, $rawToken] = invitationFor('nieuw@klant.nl');

    $this->post("/invitation/{$rawToken}", [
        'password' => 'Wachtwoord!2026',
        'password_confirmation' => 'Wachtwoord!2026',
    ])->assertRedirect();

    $mail = (new CustomerAcceptedInvitation($customer->fresh()))->toMail(new User);

    expect($mail->subject)->toBe('Klant heeft account aangemaakt')
        ->and((string) $mail->render())
        ->toContain('Bakkerij De Hoek')
        ->toContain('nieuw@klant.nl');
});

test('een mailstoring laat het aanmaken van het account niet mislukken', function () {
    Notification::fake();
    Notification::shouldReceive('send')->andThrow(new RuntimeException('mailserver plat'));

    [$customer, $rawToken] = invitationFor();
    adminUser();

    $this->post("/invitation/{$rawToken}", [
        'password' => 'Wachtwoord!2026',
        'password_confirmation' => 'Wachtwoord!2026',
    ])->assertRedirect();

    expect($customer->fresh()->user_id)->not->toBeNull();
});
