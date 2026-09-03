<?php

use Illuminate\Validation\Rules\Password;

/**
 * Klanten moeten een wachtwoord van minimaal 8 tekens kunnen kiezen. De overige
 * eisen blijven staan: hoofd- en kleine letters, een cijfer, een symbool, en het
 * wachtwoord mag niet in een bekend datalek voorkomen.
 */
test('de productieregels vragen minimaal 8 tekens', function () {
    app()['env'] = 'production';

    $rule = Password::default();

    expect((new ReflectionProperty($rule, 'min'))->getValue($rule))->toBe(8);
});

test('de productieregels houden de overige eisen overeind', function () {
    app()['env'] = 'production';

    $rule = Password::default();

    $reads = fn (string $property) => (new ReflectionProperty($rule, $property))->getValue($rule);

    expect($reads('mixedCase'))->toBeTrue()
        ->and($reads('letters'))->toBeTrue()
        ->and($reads('numbers'))->toBeTrue()
        ->and($reads('symbols'))->toBeTrue()
        ->and($reads('uncompromised'))->toBeTrue();
});

test('een wachtwoord van 8 tekens wordt geaccepteerd', function () {
    // Zonder uncompromised(), zodat de test geen netwerkverzoek doet.
    $rule = Password::min(8)->mixedCase()->letters()->numbers()->symbols();

    $validator = validator(
        ['password' => 'Abcd12!x'],
        ['password' => $rule],
    );

    expect(strlen('Abcd12!x'))->toBe(8)
        ->and($validator->passes())->toBeTrue();
});

test('een wachtwoord van 7 tekens wordt geweigerd', function () {
    $rule = Password::min(8)->mixedCase()->letters()->numbers()->symbols();

    $validator = validator(
        ['password' => 'Abc12!x'],
        ['password' => $rule],
    );

    expect(strlen('Abc12!x'))->toBe(7)
        ->and($validator->fails())->toBeTrue();
});
