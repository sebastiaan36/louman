<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the administrators that a customer's login address was changed from
 * the admin screens, so the change is visible to more than the person who
 * made it.
 */
class CustomerEmailChanged extends Notification
{
    public function __construct(
        public Customer $customer,
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
            ->subject('E-mailadres van een klant gewijzigd')
            ->greeting('E-mailadres gewijzigd')
            ->line("Het inlogadres van {$this->customer->company_name} is aangepast in het klantportaal.")
            ->line("**Klant:** {$this->customer->company_name}")
            ->line("**Vorige e-mailadres:** {$this->previousEmail}")
            ->line("**Nieuwe e-mailadres:** {$this->newEmail}")
            ->action('Bekijk de klant', route('admin.customers.show', $this->customer))
            ->line('De klant heeft hier ook bericht van gekregen, op het oude en het nieuwe adres.');
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
            'previous_email' => $this->previousEmail,
            'new_email' => $this->newEmail,
        ];
    }
}
