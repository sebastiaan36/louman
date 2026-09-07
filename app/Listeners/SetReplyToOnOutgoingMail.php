<?php

namespace App\Listeners;

use App\Models\Setting;
use Illuminate\Mail\Events\MessageSending;

class SetReplyToOnOutgoingMail
{
    /**
     * Put the configured reply-to address on every outgoing mail, so a reply
     * reaches the shop instead of the no-reply sender the mail server uses.
     *
     * A mail that sets its own reply-to keeps it.
     */
    public function handle(MessageSending $event): void
    {
        try {
            $replyTo = Setting::get(Setting::MAIL_REPLY_TO);
        } catch (\Throwable) {
            // Settings table may be unavailable (e.g. during migrations); skip.
            return;
        }

        if (! $replyTo) {
            return;
        }

        $message = $event->message;

        if ($message->getReplyTo() !== []) {
            return;
        }

        $message->addReplyTo($replyTo);
    }
}
