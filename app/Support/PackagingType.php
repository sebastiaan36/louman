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
    public const ALL = ['tas', 'doos', 'krat', 'zwarte_cbl', 'rood_e2'];

    /**
     * Get the Dutch label for a packaging type.
     */
    public static function label(?string $type): ?string
    {
        return match ($type) {
            'tas' => 'Tas',
            'doos' => 'Doos',
            'krat' => 'Krat',
            'zwarte_cbl' => 'Zwarte CBL',
            'rood_e2' => 'Rood E2',
            default => null,
        };
    }

    /**
     * The validation rule for a packaging type, e.g. "in:tas,doos,krat,zwarte_cbl,rood_e2".
     */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::ALL);
    }
}
