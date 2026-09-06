<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Build the Dutch verification mail. The signed URL is built by the parent,
     * so the expiry and signature keep working as configured.
     */
    protected function buildMailMessage($url): MailMessage
    {
        $minutes = config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Bevestig uw e-mailadres')
            ->greeting('Welkom bij Slagerij Louman')
            ->line('Bedankt voor uw aanmelding bij het B2B klantportaal. Klik op de knop hieronder om te bevestigen dat dit e-mailadres van u is.')
            ->action('E-mailadres bevestigen', $url)
            ->line("Deze link verloopt over {$minutes} minuten.")
            ->line('Zodra uw aanmelding is goedgekeurd, ontvangt u daarvan bericht en kunt u bestellingen plaatsen.')
            ->line('Heeft u zich niet aangemeld? Dan hoeft u niets te doen.')
            ->salutation("Met vriendelijke groet,  \nSlagerij Louman Jordaan");
    }
}
