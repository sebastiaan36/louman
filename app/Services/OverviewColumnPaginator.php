<?php

namespace App\Services;

use Illuminate\Support\Facades\View;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;

/**
 * Lays out the customer-overview cards column-major: column 1 is filled
 * top-to-bottom completely, then column 2, then the next page — exactly the
 * newspaper flow the order overview requires.
 *
 * mPDF's own column feature cannot do this without splitting cards, so instead
 * each card's exact rendered height is MEASURED by mPDF (no PHP height
 * estimation) and the cards are then distributed over columns and pages. Cards
 * are therefore never split.
 */
class OverviewColumnPaginator
{
    /**
     * One of the two card columns, in millimetres. Page content width is
     * A4 (210) minus the renderer's 4.2mm left/right margins, minus the gap,
     * split in two.
     */
    private const COLUMN_WIDTH_MM = 98.3;

    private const GAP_MM = 5.0;

    /**
     * Usable height for cards: A4 (297) minus the renderer's top (24) and
     * bottom (6) margins, with a small safety margin so a column never spills.
     */
    private const USABLE_HEIGHT_MM = 262.0;

    public function columnWidthMm(): float
    {
        return self::COLUMN_WIDTH_MM;
    }

    public function gapMm(): float
    {
        return self::GAP_MM;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $dayGroups
     * @return array<string, array<int, array{left: array<int, array<string, mixed>>, right: array<int, array<string, mixed>>}>>
     */
    public function paginate(array $dayGroups): array
    {
        $heights = $this->measureCardHeights($dayGroups);

        $result = [];
        foreach ($dayGroups as $day => $customers) {
            $result[$day] = $this->distribute($customers, $heights);
        }

        return $result;
    }

    /**
     * Measure each card's exact rendered height (mm) at the column width by
     * writing them into a single, very tall off-screen mPDF page.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $dayGroups
     * @return array<int|string, float>
     */
    private function measureCardHeights(array $dayGroups): array
    {
        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [self::COLUMN_WIDTH_MM, 100000],
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'default_font' => 'dejavusans',
            'default_font_size' => 8,
            'tempDir' => $tempDir,
        ]);

        $mpdf->WriteHTML(View::make('pdf.partials.overview-css')->render(), HTMLParserMode::HEADER_CSS);

        $heights = [];
        $previousY = $mpdf->y;

        foreach ($dayGroups as $customers) {
            foreach ($customers as $customer) {
                $mpdf->WriteHTML(
                    View::make('pdf.partials.customer-card', ['customer' => $customer])->render(),
                    HTMLParserMode::HTML_BODY
                );
                $heights[$this->key($customer)] = $mpdf->y - $previousY;
                $previousY = $mpdf->y;
            }
        }

        return $heights;
    }

    /**
     * Greedily fill column 1, then column 2, then a new page.
     *
     * @param  array<int, array<string, mixed>>  $customers
     * @param  array<int|string, float>  $heights
     * @return array<int, array{left: array<int, array<string, mixed>>, right: array<int, array<string, mixed>>}>
     */
    private function distribute(array $customers, array $heights): array
    {
        $pages = [];
        $page = ['left' => [], 'right' => []];
        $column = 'left';
        $columnHeight = 0.0;

        foreach ($customers as $customer) {
            $height = $heights[$this->key($customer)] ?? 0.0;

            if ($columnHeight > 0 && $columnHeight + $height > self::USABLE_HEIGHT_MM) {
                if ($column === 'left') {
                    $column = 'right';
                } else {
                    $pages[] = $page;
                    $page = ['left' => [], 'right' => []];
                    $column = 'left';
                }
                $columnHeight = 0.0;
            }

            $page[$column][] = $customer;
            $columnHeight += $height;
        }

        if ($page['left'] !== [] || $page['right'] !== []) {
            $pages[] = $page;
        }

        return $pages;
    }

    /**
     * @param  array<string, mixed>  $customer
     */
    private function key(array $customer): int|string
    {
        return $customer['id'] ?? $customer['company_name'];
    }
}
