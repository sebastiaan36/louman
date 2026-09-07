<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A question a customer asked through the help button in the portal.
 *
 * The reply-to is the customer's own address, so answering is a matter of
 * hitting reply instead of copying the address out of the message.
 */
class CustomerQuestion extends Notification
{
    public function __construct(
        public Customer $customer,
        public string $question,
        public ?string $replyTo = null
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
        $accountEmail = $this->customer->user?->email;
        $email = $this->replyTo ?: $accountEmail;

        $message = (new MailMessage)
            ->subject('Vraag van '.$this->customer->company_name)
            ->greeting('Vraag via het klantportaal')
            ->line("**Klant:** {$this->customer->company_name}")
            ->line('**Klantnummer:** '.($this->customer->customer_number ?: 'onbekend'))
            ->line('**Antwoorden naar:** '.($email ?: 'onbekend'))
            ->when(
                $accountEmail && $email !== $accountEmail,
                fn (MailMessage $mail) => $mail->line("**Let op:** het accountadres is {$accountEmail}."),
            )
            ->line('**Telefoon:** '.($this->customer->phone_number ?: 'niet opgegeven'))
            ->line('---')
            ->line($this->question)
            ->action('Bekijk de klant', route('admin.customers.show', $this->customer));

        return $email ? $message->replyTo($email, $this->customer->company_name) : $message;
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
            'question' => $this->question,
        ];
    }
}
