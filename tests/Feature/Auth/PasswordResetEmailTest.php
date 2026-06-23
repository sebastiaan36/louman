<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

test('wachtwoord-reset-mail is in het Nederlands met juiste afsluiting', function () {
    $user = User::factory()->make(['email' => 'klant@voorbeeld.nl']);

    $mail = (new ResetPasswordNotification('token123'))->toMail($user);

    expect($mail->subject)->toBe('Wachtwoord opnieuw instellen');
    expect($mail->greeting)->toBe('Hallo!');
    expect($mail->actionText)->toBe('Wachtwoord opnieuw instellen');
    expect($mail->actionUrl)->toContain('token123');
    expect($mail->introLines[0])->toContain('Je ontvangt deze e-mail');
    expect($mail->salutation)->toContain('Slagerij Louman Jordaan');
});

test('gerenderde wachtwoord-reset-mail bevat het logo en geen Engelse tekst', function () {
    $user = User::factory()->make(['email' => 'klant@voorbeeld.nl']);

    $html = (string) (new ResetPasswordNotification('token123'))->toMail($user)->render();

    expect($html)->toContain('/storage/img/Logo.png');
    expect($html)->toContain('Kopieer en plak');
    expect($html)->not->toContain('Regards');
    expect($html)->not->toContain('having trouble');
});

test('gebruiker verstuurt de Nederlandse wachtwoord-reset notificatie', function () {
    Notification::fake();

    $user = User::factory()->create();
    $user->sendPasswordResetNotification('token123');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});
