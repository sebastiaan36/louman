<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    /**
     * Build the Dutch password reset mail. The reset URL is built by the
     * parent (so any Fortify URL customization keeps working).
     */
    protected function buildMailMessage($url): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords', 'users').'.expire', 60);

        return (new MailMessage)
            ->subject('Wachtwoord opnieuw instellen')
            ->greeting('Hallo!')
            ->line('Je ontvangt deze e-mail omdat we een verzoek hebben ontvangen om het wachtwoord van je account opnieuw in te stellen.')
            ->action('Wachtwoord opnieuw instellen', $url)
            ->line("Deze link verloopt over {$minutes} minuten.")
            ->line('Heb je geen wachtwoordherstel aangevraagd? Dan hoef je niets te doen.')
            ->salutation("Met vriendelijke groet,  \nSlagerij Louman Jordaan");
    }
}
