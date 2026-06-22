<?php

use App\Services\OverviewColumnPaginator;

function overviewCustomer(int $i, int $products = 0): array
{
    $items = [];
    for ($j = 1; $j <= $products; $j++) {
        $items[] = ['article_number' => (string) $j, 'title' => "Product $j", 'weight' => null, 'quantity' => 1];
    }

    return [
        'id' => $i,
        'number' => str_pad((string) $i, 3, '0', STR_PAD_LEFT),
        'company_name' => "Klant $i",
        'phone_number' => '020-1234567',
        'is_pickup' => false,
        'products' => $items,
        'notes' => [],
        'packaging_notes' => null,
    ];
}

test('paginator verdeelt kaarten column-major en behoudt volgorde', function () {
    $customers = array_map(fn ($i) => overviewCustomer($i), range(1, 60));

    $result = app(OverviewColumnPaginator::class)->paginate(['maandag' => $customers]);
    $pages = $result['maandag'];

    // Concatenating left-then-right per page, across pages, must yield the
    // original order 1..60 — which proves column-major fill (left before right)
    // and that no card is lost or reordered.
    $flat = collect($pages)
        ->flatMap(fn ($page) => array_merge($page['left'], $page['right']))
        ->pluck('id')
        ->all();

    expect($flat)->toBe(range(1, 60));

    // On a full first page, the left column is filled before the right column.
    expect($pages[0]['right'])->not->toBeEmpty();
    expect(max(array_column($pages[0]['left'], 'id')))
        ->toBeLessThan(min(array_column($pages[0]['right'], 'id')));
});

test('paginator houdt een lange kaart heel in één kolom', function () {
    $customers = [overviewCustomer(1, 2), overviewCustomer(2, 14), overviewCustomer(3, 2)];

    $result = app(OverviewColumnPaginator::class)->paginate(['maandag' => $customers]);

    $allIds = collect($result['maandag'])
        ->flatMap(fn ($page) => array_merge($page['left'], $page['right']))
        ->pluck('id')
        ->all();

    expect($allIds)->toBe([1, 2, 3]);
});
