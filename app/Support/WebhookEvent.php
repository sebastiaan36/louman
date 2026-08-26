<?php

namespace App\Support;

/**
 * The events the portal pushes to subscribed endpoints. An endpoint only
 * receives the events it is subscribed to.
 */
class WebhookEvent
{
    public const OrderCreated = 'order.created';

    public const OrderStatusChanged = 'order.status_changed';

    public const CustomerRegistered = 'customer.registered';

    public const CustomerApproved = 'customer.approved';

    public const Ping = 'ping';

    /**
     * All events an endpoint can subscribe to.
     *
     * @var list<string>
     */
    public const ALL = [
        self::OrderCreated,
        self::OrderStatusChanged,
        self::CustomerRegistered,
        self::CustomerApproved,
        self::Ping,
    ];

    /**
     * Get the Dutch description for an event.
     */
    public static function label(string $event): string
    {
        return match ($event) {
            self::OrderCreated => 'Nieuwe bestelling geplaatst',
            self::OrderStatusChanged => 'Bestelstatus gewijzigd in het portaal',
            self::CustomerRegistered => 'Nieuwe klant aangemeld',
            self::CustomerApproved => 'Klant goedgekeurd',
            self::Ping => 'Testbericht',
            default => $event,
        };
    }
}
