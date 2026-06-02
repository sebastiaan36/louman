<?php

namespace App\Mail;

use App\Models\CustomerInvitation as CustomerInvitationModel;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CustomerInvitation extends Mailable
{
    public function __construct(
        public CustomerInvitationModel $invitation,
        public string $rawToken,
    ) {
        $this->invitation->loadMissing('customer');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Uitnodiging voor het Slagerij Louman klantportaal',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-invitation',
            with: [
                'companyName' => $this->invitation->customer->company_name,
                'acceptUrl' => route('customer.invitation.show', ['token' => $this->rawToken]),
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
