<?php

namespace App\Support;

/**
 * The abilities (scopes) an API token can be granted. A token only gets the
 * abilities the integration actually needs; every endpoint checks its own.
 */
class ApiAbility
{
    public const ProductsRead = 'products:read';

    public const ProductsWrite = 'products:write';

    public const CustomersRead = 'customers:read';

    public const CustomersWrite = 'customers:write';

    public const PricesWrite = 'prices:write';

    public const OrdersRead = 'orders:read';

    public const OrdersWrite = 'orders:write';

    /**
     * All abilities, in the order they are presented when issuing a token.
     *
     * @var list<string>
     */
    public const ALL = [
        self::ProductsRead,
        self::ProductsWrite,
        self::CustomersRead,
        self::CustomersWrite,
        self::PricesWrite,
        self::OrdersRead,
        self::OrdersWrite,
    ];

    /**
     * Get the Dutch description for an ability.
     */
    public static function label(string $ability): string
    {
        return match ($ability) {
            self::ProductsRead => 'Artikelen lezen',
            self::ProductsWrite => 'Artikelen aanmaken en bijwerken',
            self::CustomersRead => 'Klanten lezen',
            self::CustomersWrite => 'Klantgegevens bijwerken',
            self::PricesWrite => 'Klantspecifieke prijzen bijwerken',
            self::OrdersRead => 'Orders lezen',
            self::OrdersWrite => 'Orderstatus bijwerken',
            default => $ability,
        };
    }
}
