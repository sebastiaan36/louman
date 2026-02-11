<?php

namespace App\Helpers;

use Carbon\Carbon;

class OrderDeadline
{
    /**
     * Get the next Monday 12:00 deadline.
     */
    public static function getNextDeadline(): Carbon
    {
        $now = Carbon::now();

        // If it's Monday before 12:00, deadline is today at 12:00
        if ($now->isMonday() && $now->hour < 12) {
            return $now->copy()->setTime(12, 0, 0);
        }

        // Otherwise, get next Monday at 12:00
        return $now->next(Carbon::MONDAY)->setTime(12, 0, 0);
    }

    /**
     * Get time remaining until deadline in a human-readable format.
     */
    public static function getTimeRemaining(): array
    {
        $deadline = self::getNextDeadline();
        $now = Carbon::now();

        $diff = $now->diff($deadline);

        return [
            'deadline' => $deadline->toIso8601String(),
            'days' => $diff->days,
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'total_hours' => $diff->days * 24 + $diff->h,
            'is_urgent' => $diff->days === 0 && $diff->h < 24, // Less than 24 hours
        ];
    }
}
