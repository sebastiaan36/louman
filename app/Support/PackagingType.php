<?php

namespace App\Support;

/**
 * How a customer's order is packed for delivery. Kept in one place because
 * the list is used by the customer screen, the order detail and the order
 * overview; the front-end mirrors it in resources/js/lib/packagingTypes.ts.
 */
class PackagingType
{
    /**
     * All selectable packaging types, in the order they are presented.
     *
     * @var list<string>
     */
    public const ALL = ['tas', 'doos', 'krat'];

    /**
     * Get the Dutch label for a packaging type.
     */
    public static function label(?string $type): ?string
    {
        return match ($type) {
            'tas' => 'Tas',
            'doos' => 'Doos',
            'krat' => 'Krat',
            default => null,
        };
    }

    /**
     * The validation rule for a packaging type, e.g. "in:tas,doos,krat".
     */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::ALL);
    }
}
