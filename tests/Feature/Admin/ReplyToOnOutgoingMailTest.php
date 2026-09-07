<?php

use App\Models\Setting;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Mail;

function verstuurTestmail(): SentMessage
{
    return Mail::raw('Testbericht', function ($message) {
        $message->to('klant@example.nl')->subject('Test');
    });
}

test('elke uitgaande mail krijgt het ingestelde antwoord-aan adres', function () {
    Setting::set(Setting::MAIL_REPLY_TO, 'worstmakerijlouman@gmail.com');

    $sent = verstuurTestmail();

    $replyTo = array_map(
        fn ($address) => $address->getAddress(),
        $sent->getSymfonySentMessage()->getOriginalMessage()->getReplyTo(),
    );

    expect($replyTo)->toBe(['worstmakerijlouman@gmail.com']);
});

test('zonder ingesteld adres wordt er geen antwoord-aan gezet', function () {
    Setting::set(Setting::MAIL_REPLY_TO, null);

    $sent = verstuurTestmail();

    expect($sent->getSymfonySentMessage()->getOriginalMessage()->getReplyTo())->toBe([]);
});

test('een mail die zelf een antwoord-aan zet houdt die', function () {
    Setting::set(Setting::MAIL_REPLY_TO, 'worstmakerijlouman@gmail.com');

    $sent = Mail::raw('Testbericht', function ($message) {
        $message->to('klant@example.nl')->subject('Test')->replyTo('anders@louman.nl');
    });

    $replyTo = array_map(
        fn ($address) => $address->getAddress(),
        $sent->getSymfonySentMessage()->getOriginalMessage()->getReplyTo(),
    );

    expect($replyTo)->toBe(['anders@louman.nl']);
});

test('het antwoord-aan adres is in te stellen in de backend', function () {
    $this->actingAs(adminUser())
        ->patch('/admin/settings', [
            'mail_order_notification' => 'info@louman-jordaan.nl',
            'mail_registration_notification' => '',
            'mail_cancellation_notification' => '',
            'mail_cc' => '',
            'mail_reply_to' => 'worstmakerijlouman@gmail.com',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Setting::get(Setting::MAIL_REPLY_TO))->toBe('worstmakerijlouman@gmail.com');
});

test('een ongeldig antwoord-aan adres wordt geweigerd', function () {
    $this->actingAs(adminUser())
        ->patch('/admin/settings', [
            'mail_order_notification' => 'info@louman-jordaan.nl',
            'mail_reply_to' => 'geen-adres',
        ])
        ->assertSessionHasErrors(['mail_reply_to' => 'Voer een geldig antwoord-aan e-mailadres in.']);
});

test('het instellingenscherm toont het antwoord-aan adres', function () {
    Setting::set(Setting::MAIL_REPLY_TO, 'worstmakerijlouman@gmail.com');

    $this->actingAs(adminUser())
        ->get('/admin/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('settings.mail_reply_to', 'worstmakerijlouman@gmail.com')
        );
});
