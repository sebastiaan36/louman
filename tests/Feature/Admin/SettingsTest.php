<?php

use App\Listeners\AddCcToOutgoingMail;
use App\Mail\OrderCancelled;
use App\Mail\OrderPlacedNotification;
use App\Models\CartItem;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Notifications\CustomerRegistered;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mime\Email;

test('beheerder ziet de instellingenpagina met huidige waarden', function () {
    $admin = adminUser();
    Setting::set(Setting::MAIL_ORDER_NOTIFICATION, 'bestellingen@zaak.nl');

    $this->actingAs($admin)
        ->get('/admin/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Settings')
            ->where('settings.mail_order_notification', 'bestellingen@zaak.nl')
        );
});

test('klant heeft geen toegang tot de instellingen', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->get('/admin/settings')
        ->assertForbidden();
});

test('beheerder kan e-mailinstellingen opslaan', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->patch('/admin/settings', [
            'mail_order_notification' => 'bestellingen@zaak.nl',
            'mail_registration_notification' => 'registraties@zaak.nl',
            'mail_cancellation_notification' => 'annuleringen@zaak.nl',
            'mail_cc' => 'kopie@zaak.nl',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Setting::get(Setting::MAIL_ORDER_NOTIFICATION))->toBe('bestellingen@zaak.nl');
    expect(Setting::get(Setting::MAIL_CC))->toBe('kopie@zaak.nl');
});

test('een ongeldig e-mailadres wordt geweigerd', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->patch('/admin/settings', ['mail_cc' => 'geen-email'])
        ->assertSessionHasErrors('mail_cc');
});

test('het CC-adres wordt aan uitgaande mail toegevoegd', function () {
    Setting::set(Setting::MAIL_CC, 'kopie@zaak.nl');

    $message = (new Email)->to('klant@example.com')->subject('Test')->text('Body');
    (new AddCcToOutgoingMail)->handle(new MessageSending($message));

    $ccs = array_map(fn ($address) => $address->getAddress(), $message->getCc());
    expect($ccs)->toContain('kopie@zaak.nl');
});

test('zonder ingesteld CC-adres wordt er geen CC toegevoegd', function () {
    $message = (new Email)->to('klant@example.com')->subject('Test')->text('Body');
    (new AddCcToOutgoingMail)->handle(new MessageSending($message));

    expect($message->getCc())->toBeEmpty();
});

test('de bestelnotificatie gaat naar het ingestelde adres', function () {
    Mail::fake();
    Setting::set(Setting::MAIL_ORDER_NOTIFICATION, 'bestellingen@zaak.nl');

    $user = customerUser();
    $customer = approvedCustomer($user);
    $product = Product::factory()->create();
    $address = DeliveryAddress::factory()->create(['customer_id' => $customer->id]);
    CartItem::create(['customer_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user)->post('/customer/orders', ['delivery_address_id' => $address->id]);

    Mail::assertSent(OrderPlacedNotification::class, fn ($mail) => $mail->hasTo('bestellingen@zaak.nl'));
});

test('de registratienotificatie gaat naar het ingestelde adres', function () {
    Notification::fake();
    Setting::set(Setting::MAIL_REGISTRATION_NOTIFICATION, 'registraties@zaak.nl');

    $this->post('/register/customer', [
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
    ]);

    Notification::assertSentOnDemand(
        CustomerRegistered::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'registraties@zaak.nl'
    );
});

test('een geannuleerde bestelling stuurt een mail naar het ingestelde adres', function () {
    Mail::fake();
    Setting::set(Setting::MAIL_CANCELLATION_NOTIFICATION, 'annuleringen@zaak.nl');

    $admin = adminUser();
    $customer = approvedCustomer();
    $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);

    $this->actingAs($admin)
        ->patch("/admin/orders/{$order->id}/status", ['status' => 'cancelled'])
        ->assertRedirect();

    Mail::assertQueued(OrderCancelled::class, fn ($mail) => $mail->hasTo('annuleringen@zaak.nl'));
});

test('zonder ingesteld annuleringsadres wordt er geen annuleringsmail verstuurd', function () {
    Mail::fake();

    $admin = adminUser();
    $customer = approvedCustomer();
    $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);

    $this->actingAs($admin)
        ->patch("/admin/orders/{$order->id}/status", ['status' => 'cancelled']);

    Mail::assertNotQueued(OrderCancelled::class);
});
