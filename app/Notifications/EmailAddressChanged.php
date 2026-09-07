<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the customer that the e-mail address of their account was changed.
 *
 * Sent to both the old and the new address: the new one because that is where
 * they log in from now on, the old one so a change they did not ask for cannot
 * happen unnoticed.
 */
class EmailAddressChanged extends Notification
{
    public function __construct(
        public string $previousEmail,
        public string $newEmail
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Het e-mailadres van uw account is gewijzigd')
            ->greeting('Uw e-mailadres is gewijzigd')
            ->line('Het e-mailadres van uw account in het Slagerij Louman B2B klantportaal is aangepast.')
            ->line("**Vorige e-mailadres:** {$this->previousEmail}")
            ->line("**Nieuwe e-mailadres:** {$this->newEmail}")
            ->line('Vanaf nu logt u in met het nieuwe adres. Uw wachtwoord blijft ongewijzigd.')
            ->line('Heeft u hier niet om gevraagd? Neem dan contact met ons op.')
            ->salutation("Met vriendelijke groet,  \nSlagerij Louman Jordaan");
    }
}
