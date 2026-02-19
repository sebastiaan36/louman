<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderShipped extends Mailable
{

    public function __construct(public Order $order)
    {
        $this->order->load(['customer.user', 'deliveryAddress', 'items.product']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bestelling #' . $this->order->id . ' is verzonden - Slagerij Louman',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-shipped',
            with: [
                'order' => $this->order,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
