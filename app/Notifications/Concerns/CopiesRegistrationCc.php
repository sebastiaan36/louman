<?php

namespace App\Notifications\Concerns;

use App\Models\Setting;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Puts the configured registration CC address on a notification.
 *
 * Separate from the CC that goes on every outgoing mail: this one only follows
 * the two moments a customer joins — a new registration and an accepted
 * invitation — so someone can be kept in the loop on those without receiving
 * every order confirmation as well.
 */
trait CopiesRegistrationCc
{
    protected function withRegistrationCc(MailMessage $message): MailMessage
    {
        try {
            $cc = Setting::get(Setting::MAIL_REGISTRATION_CC);
        } catch (\Throwable) {
            // Settings table may be unavailable (e.g. during migrations); skip.
            return $message;
        }

        return $cc ? $message->cc($cc) : $message;
    }
}
