<?php

/**
 * The add-to-cart button switches to 'Toegevoegd' once the product is in the
 * cart. That only reads as a state when it is the exception on screen, so the
 * demo data deliberately keeps the cart, the quick-order list and the top of
 * the catalogue apart.
 */
$resources = dirname(__DIR__, 2).'/resources';

test('de knop toont Toegevoegd zodra het product in de winkelwagen zit', function (string $file) use ($resources) {
    expect(file_get_contents("{$resources}/{$file}"))
        ->toContain("product.in_cart ? 'Toegevoegd' : 'Toevoegen'");
})->with([
    'js/components/ProductCard.vue',
    'js/pages/customer/Favorites.vue',
]);

test('de knop krijgt een eigen kleur zodra het product in de winkelwagen zit', function (string $file) use ($resources) {
    expect(file_get_contents("{$resources}/{$file}"))
        ->toContain("import { inCartButtonClasses } from '@/lib/cart';")
        ->toContain("product.in_cart ? inCartButtonClasses : ''");
})->with([
    'js/components/ProductCard.vue',
    'js/pages/customer/Favorites.vue',
]);

test('de kleur voor de winkelwagenstatus werkt in beide thema\'s', function () use ($resources) {
    $source = file_get_contents("{$resources}/js/lib/cart.ts");

    expect($source)
        ->toContain('bg-emerald-600')
        ->toContain('hover:bg-emerald-700')
        ->toContain('dark:bg-emerald-600');
});

test('de details-knop rekt niet mee, zodat geen van beide labels wordt afgekapt', function () use ($resources) {
    $source = file_get_contents("{$resources}/js/components/ProductCard.vue");

    // Alleen de winkelwagenknop krijgt flex-1; Details houdt de breedte van
    // zijn eigen tekst. Past het niet, dan breekt de rij af in plaats van de
    // tekst af te kappen.
    expect(substr_count($source, 'flex-1'))->toBe(1)
        ->and($source)->toContain('flex-wrap')
        ->and($source)->not->toContain('truncate');
});
