<?php

/**
 * Het wachtwoordveld met oogje staat in één component, zodat inloggen,
 * registreren en de uitnodigingspagina er hetzelfde uitzien. Deze test valt om
 * zodra een scherm de markup weer zelf gaat nabouwen.
 */
$resources = dirname(__DIR__, 2).'/resources';

test('het gedeelde wachtwoordveld heeft een knop om het wachtwoord te tonen', function () use ($resources) {
    $source = file_get_contents("{$resources}/js/components/PasswordInput.vue");

    expect($source)
        ->toContain("showPassword ? 'text' : 'password'")
        ->toContain("showPassword ? 'Wachtwoord verbergen' : 'Wachtwoord tonen'")
        ->toContain('inheritAttrs: false');
});

test('de schermen waar een klant een wachtwoord kiest gebruiken hetzelfde veld', function (string $page) use ($resources) {
    $source = file_get_contents("{$resources}/js/pages/auth/{$page}");

    expect($source)
        ->toContain("import PasswordInput from '@/components/PasswordInput.vue';")
        ->toContain('<PasswordInput');
})->with(['Login.vue', 'CustomerRegister.vue', 'AcceptInvitation.vue']);

test('beide wachtwoordvelden hebben het oogje', function (string $page) use ($resources) {
    $source = file_get_contents("{$resources}/js/pages/auth/{$page}");

    expect(substr_count($source, '<PasswordInput'))->toBe(2)
        ->and($source)->toContain('id="password"')
        ->and($source)->toContain('id="password_confirmation"');
})->with(['CustomerRegister.vue', 'AcceptInvitation.vue']);

test('geen enkel scherm bouwt de knop nog zelf na', function () use ($resources) {
    $offenders = [];

    foreach (glob("{$resources}/js/pages/**/*.vue") as $file) {
        if (str_contains(file_get_contents($file), 'showPassword')) {
            $offenders[] = basename($file);
        }
    }

    expect($offenders)->toBe([]);
});

test('elk wachtwoordveld in de front-end gebruikt het gedeelde component', function () use ($resources) {
    $offenders = [];

    $files = array_merge(
        glob("{$resources}/js/pages/**/*.vue"),
        glob("{$resources}/js/components/*.vue"),
    );

    foreach ($files as $file) {
        // Een kaal type="password" betekent dat het veld het oogje mist.
        if (str_contains(file_get_contents($file), 'type="password"')) {
            $offenders[] = basename($file);
        }
    }

    expect($offenders)->toBe([]);
});
