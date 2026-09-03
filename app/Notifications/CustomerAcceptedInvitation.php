<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the administrators that an invited customer has set a password and
 * completed their account. Unlike a self-registration there is nothing to
 * approve — the customer was created by an administrator and is already
 * approved — so this is a confirmation, not a to-do.
 */
class CustomerAcceptedInvitation extends Notification
{
    public function __construct(
        public Customer $customer
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
            ->subject('Klant heeft account aangemaakt')
            ->greeting('Account aangemaakt')
            ->line("{$this->customer->company_name} heeft via de uitnodigingslink een wachtwoord ingesteld en kan nu inloggen in het klantportaal.")
            ->line("**Bedrijfsnaam:** {$this->customer->company_name}")
            ->line('**E-mailadres:** '.($this->customer->user?->email ?? 'onbekend'))
            ->action('Bekijk de klant', route('admin.customers.show', $this->customer))
            ->line('Er is verder niets te doen; deze klant was al goedgekeurd.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'customer_id' => $this->customer->id,
            'company_name' => $this->customer->company_name,
        ];
    }
}
