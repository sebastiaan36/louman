<?php

use App\Support\DeliveryDay;

test('zaterdag en zondag zijn geen bezorgdag meer', function () {
    expect(DeliveryDay::ALL)
        ->not->toContain('zaterdag')
        ->not->toContain('zondag')
        ->toBe(['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'ophalen']);
});

test('elke bezorgdag heeft een label', function (string $day) {
    expect(DeliveryDay::label($day))->not->toBe($day);
})->with(DeliveryDay::ALL);

test('de validatieregel dekt precies de toegestane dagen', function () {
    expect(DeliveryDay::rule())->toBe('in:maandag,dinsdag,woensdag,donderdag,vrijdag,ophalen');
});

/**
 * The front-end keeps its own copy of the list so the dropdowns can render
 * without a round trip. This check keeps that copy honest: a day dropped on
 * the server but left in the Vue list is exactly how zaterdag and zondag
 * stayed selectable after the delivery route had already dropped them.
 */
test('de lijst in de front-end loopt gelijk met die op de server', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/js/lib/deliveryDays.ts');

    preg_match_all("/value: '([^']+)'/", $source, $matches);

    expect($matches[1])->toBe(DeliveryDay::ALL);
});

test('geen enkel scherm heeft nog een eigen dagenlijst', function () {
    $files = array_merge(
        glob(dirname(__DIR__, 2).'/resources/js/pages/admin/*.vue'),
        glob(dirname(__DIR__, 2).'/app/Http/Controllers/**/*.php'),
        glob(dirname(__DIR__, 2).'/app/Http/Requests/**/**/*.php'),
    );

    $offenders = [];

    foreach ($files as $file) {
        $source = file_get_contents($file);

        // A hardcoded weekend day means the screen bypasses DeliveryDay.
        if (preg_match("/'(zaterdag|zondag)'/", $source)) {
            $offenders[] = basename($file);
        }
    }

    expect($offenders)->toBe([]);
});
