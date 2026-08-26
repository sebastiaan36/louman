<?php

/**
 * The price notice tells customers that the portal shows standard prices and
 * that their agreed prices are applied on the invoice. It has to stay on every
 * customer page that puts a price in front of them, so this guards against it
 * quietly disappearing from one of them.
 */
$resources = dirname(__DIR__, 2).'/resources';

$pagesShowingPrices = [
    'Products.vue',
    'ProductDetail.vue',
    'Favorites.vue',
    'Cart.vue',
    'OrderDetail.vue',
];

test('de prijsmelding staat op elke klantpagina die prijzen toont', function (string $page) use ($resources) {
    $source = file_get_contents("{$resources}/js/pages/customer/{$page}");

    expect($source)
        ->toContain("import PriceNotice from '@/components/PriceNotice.vue';")
        ->toContain('<PriceNotice');
})->with($pagesShowingPrices);

test('de prijsmelding bevat de afgesproken tekst', function () use ($resources) {
    $source = file_get_contents("{$resources}/js/components/PriceNotice.vue");

    $normalised = preg_replace('/\s+/', ' ', strip_tags($source));

    expect($normalised)->toContain(
        'De getoonde prijzen in het klantportaal zijn onze standaardprijzen. '
        .'Uw persoonlijk afgesproken prijzen worden bij de facturatie automatisch '
        .'toegepast en zijn terug te zien op uw factuur.'
    );
});
