<?php

namespace App\Support;

/**
 * The days a customer can be assigned to for delivery. Kept in one place
 * because the list is used by the customer screens, the delivery route and the
 * integration API; when it lived in each of those separately, dropping a day
 * left the others behind.
 */
class DeliveryDay
{
    /**
     * All selectable delivery days, in the order they are presented.
     *
     * @var list<string>
     */
    public const ALL = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'ophalen'];

    /**
     * Get the Dutch label for a delivery day.
     */
    public static function label(string $day): string
    {
        return match ($day) {
            'maandag' => 'Maandag',
            'dinsdag' => 'Dinsdag',
            'woensdag' => 'Woensdag',
            'donderdag' => 'Donderdag',
            'vrijdag' => 'Vrijdag',
            'ophalen' => 'Ophalen',
            default => $day,
        };
    }

    /**
     * The validation rule for a delivery day, e.g. "in:maandag,dinsdag,...".
     */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::ALL);
    }
}
