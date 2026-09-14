<?php

use App\Support\PackagingType;

test('de verpakkingssoorten zijn tas, doos en krat', function () {
    expect(PackagingType::ALL)->toBe(['tas', 'doos', 'krat'])
        ->and(PackagingType::rule())->toBe('in:tas,doos,krat')
        ->and(PackagingType::label('doos'))->toBe('Doos')
        ->and(PackagingType::label(null))->toBeNull()
        ->and(PackagingType::label('zak'))->toBeNull();
});

test('de lijst in de front-end loopt gelijk met die op de server', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/js/lib/packagingTypes.ts');

    preg_match_all("/value: '([^']+)'/", $source, $matches);

    expect($matches[1])->toBe(PackagingType::ALL);
});
