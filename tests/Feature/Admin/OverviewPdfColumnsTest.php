<?php

/**
 * Het besteloverzicht dat in de zaak wordt uitgedraaid: artikelnummer, aantal,
 * omschrijving. Het aantal staat bewust vóór de omschrijving, omdat dat de
 * volgorde is waarin het wordt afgelezen bij het klaarzetten.
 */
test('de kolommen staan in de volgorde artikelnummer, aantal, omschrijving', function () {
    $kaart = file_get_contents(dirname(__DIR__, 3).'/resources/views/pdf/partials/customer-card.blade.php');

    $art = strpos($kaart, '<td class="art">');
    $qty = strpos($kaart, '<td class="qty">');
    $name = strpos($kaart, '<td class="name">');

    expect($art)->not->toBeFalse()
        ->and($qty)->not->toBeFalse()
        ->and($name)->not->toBeFalse()
        ->and($art)->toBeLessThan($qty)
        ->and($qty)->toBeLessThan($name);
});

test('de omschrijving houdt de marge aan de rechterkant', function () {
    $css = file_get_contents(dirname(__DIR__, 3).'/resources/views/pdf/partials/overview-css.blade.php');

    // De omschrijving staat nu rechts, dus die moet de padding overnemen die
    // eerder op de aantalkolom stond.
    expect($css)->toContain('table.product .name {');
});
