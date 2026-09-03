<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as Notifier;

/**
 * Sends a notification to whoever watches the inbox at Louman: the address
 * configured under instellingen, or every administrator when none is set.
 *
 * Mail goes out during the request, so a failing mail server may never break
 * what the visitor was doing — the registration or the account is already
 * saved by the time this runs. Failures are logged instead.
 */
class AdminNotifier
{
    public static function send(Notification $notification): void
    {
        try {
            $address = Setting::get(Setting::MAIL_REGISTRATION_NOTIFICATION);

            if ($address) {
                Notifier::route('mail', $address)->notify($notification);

                return;
            }

            Notifier::send(User::where('role', 'admin')->get(), $notification);
        } catch (\Exception $e) {
            Log::error('Failed to notify administrators', [
                'notification' => $notification::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
