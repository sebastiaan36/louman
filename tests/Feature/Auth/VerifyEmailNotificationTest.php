<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

test('een nieuwe klant krijgt de nederlandse verificatiemail', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create(['role' => 'customer']);

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('de verificatiemail is nederlands en spreekt de klant met u aan', function () {
    $user = User::factory()->unverified()->create(['role' => 'customer']);

    $mail = (new VerifyEmailNotification)->toMail($user);
    $body = (string) $mail->render();

    expect($mail->subject)->toBe('Bevestig uw e-mailadres')
        ->and($body)
        ->toContain('E-mailadres bevestigen')
        ->toContain('Welkom bij Slagerij Louman')
        ->toContain('Slagerij Louman Jordaan')
        ->not->toContain('Verify Email Address')
        ->not->toContain('Please click the button below')
        ->not->toContain('Regards');
});

test('de verificatiemail bevat een werkende verificatielink', function () {
    $user = User::factory()->unverified()->create(['role' => 'customer']);

    $body = (string) (new VerifyEmailNotification)->toMail($user)->render();

    expect($body)->toContain('/email/verify/'.$user->id)
        ->and($body)->toContain('signature=');
});
