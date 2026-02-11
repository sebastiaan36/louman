<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Customer $customer
    ) {
    }

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
            ->subject('Nieuwe Klantregistratie')
            ->greeting('Nieuwe klant geregistreerd')
            ->line("Er heeft zich een nieuwe klant geregistreerd bij het B2B portaal.")
            ->line("**Bedrijfsnaam:** {$this->customer->company_name}")
            ->line("**Contactpersoon:** {$this->customer->contact_person}")
            ->line("**Email:** {$this->customer->user->email}")
            ->line("**KvK nummer:** {$this->customer->kvk_number}")
            ->action('Bekijk en keur goed', route('admin.customers.pending'))
            ->line('Klik op de knop hierboven om de klant goed te keuren in het admin paneel.');
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
