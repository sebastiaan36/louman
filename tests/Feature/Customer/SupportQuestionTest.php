<?php

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CustomerQuestion;
use Illuminate\Support\Facades\Notification;

test('een klant kan een vraag versturen', function () {
    Notification::fake();

    Setting::set(Setting::MAIL_SUPPORT_NOTIFICATION, 'vragen@louman.nl');
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->post('/customer/support', ['question' => 'Wanneer wordt mijn bestelling geleverd?'])
        ->assertRedirect()
        ->assertSessionHas('success');

    Notification::assertSentOnDemand(
        CustomerQuestion::class,
        fn (CustomerQuestion $n, $channels, $notifiable) => $n->question === 'Wanneer wordt mijn bestelling geleverd?'
            && $n->customer->is($customer)
            && $notifiable->routes['mail'] === 'vragen@louman.nl',
    );
});

test('zonder ingesteld adres gaat de vraag naar alle beheerders', function () {
    Notification::fake();

    Setting::set(Setting::MAIL_SUPPORT_NOTIFICATION, null);
    $admin = adminUser();
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->post('/customer/support', ['question' => 'Een vraag over de levering.']);

    Notification::assertSentTo($admin, CustomerQuestion::class);
});

test('de mail is te beantwoorden op het adres van de klant', function () {
    $customer = approvedCustomer();
    $customer->user->forceFill(['email' => 'klant@bedrijf.nl'])->save();

    $mail = (new CustomerQuestion($customer->fresh(), 'Mijn vraag'))->toMail(new User);

    expect($mail->replyTo[0][0])->toBe('klant@bedrijf.nl')
        ->and($mail->subject)->toBe('Vraag van '.$customer->company_name);
});

test('de mail bevat de vraag en de klantgegevens', function () {
    $customer = approvedCustomer();
    $customer->update(['customer_number' => 'K-042', 'phone_number' => '0301234567']);

    $body = (string) (new CustomerQuestion($customer->fresh(), 'Waar blijft mijn bestelling?'))
        ->toMail(new User)
        ->render();

    expect($body)
        ->toContain('Waar blijft mijn bestelling?')
        ->toContain($customer->company_name)
        ->toContain('K-042')
        ->toContain('0301234567');
});

test('een lege vraag wordt geweigerd', function () {
    Notification::fake();

    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->post('/customer/support', ['question' => ''])
        ->assertSessionHasErrors(['question' => 'Vul uw vraag in.']);

    Notification::assertNothingSent();
});

test('een te korte vraag wordt geweigerd', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->post('/customer/support', ['question' => 'hoi'])
        ->assertSessionHasErrors('question');
});

test('een beheerder wordt naar het dashboard gestuurd en verstuurt geen vraag', function () {
    Notification::fake();

    $this->actingAs(adminUser())
        ->post('/customer/support', ['question' => 'Een vraag van een beheerder.'])
        ->assertRedirect(route('dashboard'));

    Notification::assertNothingSent();
});

test('een bezoeker zonder account kan geen vraag versturen', function () {
    $this->post('/customer/support', ['question' => 'Een vraag zonder account.'])
        ->assertRedirect(route('login'));
});

test('de vraag wordt vastgelegd in het auditlogboek', function () {
    Notification::fake();

    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->post('/customer/support', ['question' => 'Een vraag over de levering.']);

    expect(AuditLog::where('action', 'customer.question')->exists())->toBeTrue();
});

test('de hulpgegevens worden gedeeld met een ingelogde klant', function () {
    Setting::set(Setting::SUPPORT_PHONE, '020-1234567');

    $customer = approvedCustomer();
    $customer->update(['company_name' => 'Bakkerij De Hoek']);

    $this->actingAs($customer->user)
        ->get('/customer/products')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('support.company_name', 'Bakkerij De Hoek')
            ->where('support.email', $customer->user->email)
            ->where('support.phone', '020-1234567')
        );
});

test('een beheerder krijgt geen hulpgegevens, dus geen knop', function () {
    $this->actingAs(adminUser())
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('support', null));
});
