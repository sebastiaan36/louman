<?php

/**
 * Een Wayfinder-routefunctie geeft een object terug: { url, method }.
 *
 * Inertia's <Link> gaat daar zelf mee om, maar een gewone <a> heeft een string
 * nodig. Wordt daar het object gezet, dan navigeert de browser naar
 * "[object Object]" en krijgt de gebruiker een 404 — zonder dat er iets
 * misgaat in de applicatie, dus geen enkele andere test merkt het.
 */
test('elke <a> met een routefunctie gebruikt de url en niet het object', function () {
    $resources = dirname(__DIR__, 2).'/resources/js';

    $files = array_merge(
        glob("{$resources}/pages/*.vue"),
        glob("{$resources}/pages/**/*.vue"),
        glob("{$resources}/components/*.vue"),
        glob("{$resources}/layouts/**/*.vue"),
    );

    $offenders = [];

    foreach ($files as $file) {
        $source = file_get_contents($file);

        // <a :href="iets()"> zonder .url erachter.
        preg_match_all('/<a\s+:href="[a-zA-Z_][a-zA-Z0-9_.]*\([^)]*\)"/', $source, $matches);

        foreach ($matches[0] as $match) {
            // .url levert de string op; toUrl() doet dat ook, voor een waarde
            // die zowel een string als een routeobject kan zijn.
            if (str_contains($match, '.url') || str_contains($match, 'toUrl(')) {
                continue;
            }

            $offenders[] = basename($file).': '.preg_replace('/\s+/', ' ', $match);
        }
    }

    expect($offenders)->toBe([]);
});
