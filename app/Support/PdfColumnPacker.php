<?php

namespace App\Support;

class PdfColumnPacker
{
    /** Approximate usable height of one column on a page (in estimate units). */
    private const COLUMN_BUDGET = 720;

    /** Estimated height of a card: base (border + padding + header). */
    private const CARD_BASE = 34;

    /** Estimated height added per product row. */
    private const PRODUCT_ROW = 10;

    /** Estimated height added by the note line. */
    private const NOTE = 14;

    /** Estimated spacing between cards. */
    private const SPACING = 6;

    /**
     * Pack customer cards into printed pages of two columns. The left column
     * fills top-to-bottom first, then the right column, based on an estimated
     * card height so a column roughly fills one page.
     *
     * @param  array<int, array{products?: array, notes?: array}>  $customers
     * @return array<int, array{left: array<int, mixed>, right: array<int, mixed>}>
     */
    public static function pages(array $customers): array
    {
        $columns = [];
        $current = [];
        $used = 0;

        foreach ($customers as $customer) {
            $height = self::CARD_BASE
                + count($customer['products'] ?? []) * self::PRODUCT_ROW
                + (! empty($customer['notes']) ? self::NOTE : 0)
                + self::SPACING;

            if (! empty($current) && $used + $height > self::COLUMN_BUDGET) {
                $columns[] = $current;
                $current = [];
                $used = 0;
            }

            $current[] = $customer;
            $used += $height;
        }

        if (! empty($current)) {
            $columns[] = $current;
        }

        $pages = [];
        foreach (array_chunk($columns, 2) as $pair) {
            $pages[] = ['left' => $pair[0], 'right' => $pair[1] ?? []];
        }

        return $pages;
    }
}
