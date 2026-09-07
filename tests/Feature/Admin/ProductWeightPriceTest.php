<?php

/**
 * Wijzigt het gewicht van een product, dan klopt de stuksprijs niet meer.
 * Voorheen moest de kiloprijs opnieuw ingetikt worden om de herberekening te
 * forceren; nu volgt de stuksprijs het gewicht vanzelf.
 */
$formulier = dirname(__DIR__, 3).'/resources/js/pages/admin/ProductForm.vue';

test('het gewicht laat de prijs opnieuw berekenen', function () use ($formulier) {
    $source = file_get_contents($formulier);

    expect($source)->toContain('watch(() => form.value.weight,');
});

test('de kiloprijs blijft leidend en de stuksprijs volgt', function () use ($formulier) {
    $source = file_get_contents($formulier);

    // Bij een gevulde kiloprijs wordt de stuksprijs herberekend...
    expect($source)->toContain('form.value.price = toMoney(perKg * kg);')
        // ...en zonder kiloprijs volgt die juist uit de stuksprijs.
        ->and($source)->toContain('form.value.price_per_kg = toMoney(price / kg);');
});
