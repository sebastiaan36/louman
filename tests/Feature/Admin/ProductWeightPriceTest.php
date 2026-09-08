<?php

/**
 * Wijzigt het gewicht van een product, dan klopt de stuksprijs niet meer.
 * Voorheen moest de kiloprijs opnieuw ingetikt worden om de herberekening te
 * forceren; nu volgt de stuksprijs het gewicht vanzelf.
 */
$formulier = dirname(__DIR__, 3).'/resources/js/pages/admin/ProductForm.vue';

test('gewicht, stuksprijs en kiloprijs houden elkaar bij', function (string $veld) use ($formulier) {
    $source = file_get_contents($formulier);

    expect($source)->toContain("watch(() => form.value.{$veld},");
})->with(['weight', 'price', 'price_per_kg']);

test('de herberekening trapt zichzelf niet opnieuw af', function () use ($formulier) {
    $source = file_get_contents($formulier);

    // Zonder deze vlag zou het bijwerken van het ene veld het andere aftrappen
    // en andersom, met afrondingsverschillen die blijven doorlopen.
    expect($source)
        ->toContain('let syncingPrices = false;')
        ->toContain('if (syncingPrices) {');
});

test('de berekening hangt niet meer aan het typen in een veld', function () use ($formulier) {
    $source = file_get_contents($formulier);

    // Met @input gebeurde er niets als een waarde ergens anders vandaan kwam.
    expect($source)
        ->not->toContain('@input="syncPricePerKgFromPrice"')
        ->not->toContain('@input="syncPriceFromPricePerKg"');
});

test('de kiloprijs blijft leidend en de stuksprijs volgt', function () use ($formulier) {
    $source = file_get_contents($formulier);

    // Bij een gevulde kiloprijs wordt de stuksprijs herberekend...
    expect($source)->toContain('form.value.price = toMoney(perKg * kg);')
        // ...en zonder kiloprijs volgt die juist uit de stuksprijs.
        ->and($source)->toContain('form.value.price_per_kg = toMoney(price / kg);');
});
