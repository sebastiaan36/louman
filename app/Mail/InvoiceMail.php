<?php

namespace App\Mail;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public CarbonInterface $invoiceDate;

    public function __construct(public Order $order)
    {
        $this->order->load(['customer.user', 'deliveryAddress', 'items.product']);
        $this->invoiceDate = now();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factuur #' . $this->order->id . ' - Slagerij Louman',
            bcc: ['facturen@louman-jordaan.nl'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'order' => $this->order,
                'invoiceDate' => $this->invoiceDate,
            ],
        );
    }

    public function attachments(): array
    {
        $invoiceDate = $this->invoiceDate;
        $order = $this->order;

        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $order,
            'invoiceDate' => $invoiceDate,
        ]);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                'factuur-' . $this->order->id . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
