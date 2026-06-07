<?php

namespace App\Support;

class OrderStatus
{
    /**
     * All order statuses in their natural lifecycle order.
     *
     * @var list<string>
     */
    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

    /**
     * Get the Dutch label for an order status.
     */
    public static function label(string $status): string
    {
        return match ($status) {
            'pending' => 'In behandeling',
            'confirmed' => 'Bevestigd',
            'completed' => 'Voltooid',
            'cancelled' => 'Geannuleerd',
            default => $status,
        };
    }
}
