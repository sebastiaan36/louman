<?php

use App\Mail\OrderShipped;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CustomerAcceptedInvitation;
use App\Notifications\CustomerRegistered;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;

function ccVan(object $notification): array
{
    $bericht = $notification->toMail(new User);

    return array_map(fn ($adres) => is_array($adres) ? $adres[0] : $adres, $bericht->cc);
}

test('de registratiemelding krijgt het ingestelde CC-adres', function () {
    Setting::set(Setting::MAIL_REGISTRATION_CC, 'kopie@louman.nl');

    $customer = Customer::factory()->approved()->create(['user_id' => customerUser()->id]);

    expect(ccVan(new CustomerRegistered($customer)))->toBe(['kopie@louman.nl']);
});

test('de melding bij een geaccepteerde uitnodiging krijgt hetzelfde CC-adres', function () {
    Setting::set(Setting::MAIL_REGISTRATION_CC, 'kopie@louman.nl');

    $customer = Customer::factory()->approved()->create(['user_id' => customerUser()->id]);

    expect(ccVan(new CustomerAcceptedInvitation($customer)))->toBe(['kopie@louman.nl']);
});

test('zonder ingesteld CC-adres wordt er niets gekopieerd', function () {
    Setting::set(Setting::MAIL_REGISTRATION_CC, null);

    $customer = Customer::factory()->approved()->create(['user_id' => customerUser()->id]);

    expect(ccVan(new CustomerRegistered($customer)))->toBe([]);
});

test('het CC-adres raakt andere mails niet', function () {
    Setting::set(Setting::MAIL_REGISTRATION_CC, 'kopie@louman.nl');

    $order = Order::factory()->create();

    expect((new OrderShipped($order))->envelope()->cc)->toBe([]);
});

test('bij een echte registratie staat het CC-adres op de verstuurde mail', function () {
    Setting::set(Setting::MAIL_REGISTRATION_NOTIFICATION, 'meldingen@louman.nl');
    Setting::set(Setting::MAIL_REGISTRATION_CC, 'kopie@louman.nl');

    $verzonden = [];

    Event::listen(
        MessageSending::class,
        function ($event) use (&$verzonden) {
            $verzonden[] = [
                'to' => array_map(fn ($a) => $a->getAddress(), $event->message->getTo()),
                'cc' => array_map(fn ($a) => $a->getAddress(), $event->message->getCc()),
            ];
        },
    );

    $this->post(route('customer.register.store'), [
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
    ])->assertSessionHasNoErrors();

    $melding = collect($verzonden)->firstWhere('to', ['meldingen@louman.nl']);

    expect($melding)->not->toBeNull()
        ->and($melding['cc'])->toContain('kopie@louman.nl');
});

test('het CC-adres is in te stellen in de backend', function () {
    $this->actingAs(adminUser())
        ->patch('/admin/settings', [
            'mail_order_notification' => 'info@louman-jordaan.nl',
            'mail_registration_notification' => 'meldingen@louman.nl',
            'mail_registration_cc' => 'kopie@louman.nl',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Setting::get(Setting::MAIL_REGISTRATION_CC))->toBe('kopie@louman.nl');
});

test('een ongeldig CC-adres wordt geweigerd', function () {
    $this->actingAs(adminUser())
        ->patch('/admin/settings', [
            'mail_order_notification' => 'info@louman-jordaan.nl',
            'mail_registration_cc' => 'geen-adres',
        ])
        ->assertSessionHasErrors([
            'mail_registration_cc' => 'Voer een geldig CC e-mailadres in voor de registratienotificatie.',
        ]);
});

test('het instellingenscherm toont het CC-adres', function () {
    Setting::set(Setting::MAIL_REGISTRATION_CC, 'kopie@louman.nl');

    $this->actingAs(adminUser())
        ->get('/admin/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('settings.mail_registration_cc', 'kopie@louman.nl'));
});
