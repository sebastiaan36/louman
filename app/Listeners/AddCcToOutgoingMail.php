<?php

namespace App\Listeners;

use App\Models\Setting;
use Illuminate\Mail\Events\MessageSending;

class AddCcToOutgoingMail
{
    /**
     * Add the configured CC address to every outgoing mail.
     */
    public function handle(MessageSending $event): void
    {
        try {
            $cc = Setting::get(Setting::MAIL_CC);
        } catch (\Throwable) {
            // Settings table may be unavailable (e.g. during migrations); skip.
            return;
        }

        if (! $cc) {
            return;
        }

        $message = $event->message;

        $recipients = array_map(
            fn ($address) => $address->getAddress(),
            array_merge($message->getTo(), $message->getCc()),
        );

        if (in_array($cc, $recipients, true)) {
            return;
        }

        $message->addCc($cc);
    }
}
