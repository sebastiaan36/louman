<?php

use App\Support\PdfColumnPacker;

function packerCustomer(int $products = 1, bool $note = true): array
{
    return [
        'products' => array_fill(0, $products, ['x' => 1]),
        'notes' => $note ? ['n'] : [],
    ];
}

test('weinig klanten staan onder elkaar in de linkerkolom op één pagina', function () {
    $pages = PdfColumnPacker::pages([packerCustomer(), packerCustomer()]);

    expect($pages)->toHaveCount(1);
    expect($pages[0]['left'])->toHaveCount(2);
    expect($pages[0]['right'])->toHaveCount(0);
});

test('linkerkolom loopt eerst vol, daarna de rechter, daarna een nieuwe pagina', function () {
    $pages = PdfColumnPacker::pages(array_map(fn () => packerCustomer(), range(1, 30)));

    // Each card ~64 units, budget 720 -> 11 per column -> columns 11,11,8 -> 2 pages.
    expect($pages)->toHaveCount(2);
    expect($pages[0]['left'])->toHaveCount(11);
    expect($pages[0]['right'])->toHaveCount(11);
    expect($pages[1]['left'])->toHaveCount(8);
    expect($pages[1]['right'])->toHaveCount(0);
});

test('packer is leeg voor geen klanten', function () {
    expect(PdfColumnPacker::pages([]))->toBe([]);
});
