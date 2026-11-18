<?php

use App\Support\PackagingType;

test('de verpakkingssoorten zijn tas, doos, krat, zwarte CBL en rood E2', function () {
    expect(PackagingType::ALL)->toBe(['tas', 'doos', 'krat', 'zwarte_cbl', 'rood_e2'])
        ->and(PackagingType::rule())->toBe('in:tas,doos,krat,zwarte_cbl,rood_e2')
        ->and(PackagingType::label('zwarte_cbl'))->toBe('Zwarte CBL')
        ->and(PackagingType::label('rood_e2'))->toBe('Rood E2')
        ->and(PackagingType::label('doos'))->toBe('Doos')
        ->and(PackagingType::label(null))->toBeNull()
        ->and(PackagingType::label('zak'))->toBeNull();
});

test('de lijst in de front-end loopt gelijk met die op de server', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/js/lib/packagingTypes.ts');

    preg_match_all("/value: '([^']+)'/", $source, $matches);

    expect($matches[1])->toBe(PackagingType::ALL);
});
