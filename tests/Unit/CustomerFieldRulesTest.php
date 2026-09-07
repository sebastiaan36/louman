<?php

use App\Rules\DutchIban;
use App\Rules\DutchPhoneNumber;
use App\Rules\DutchPostalCode;
use App\Rules\DutchVatNumber;
use App\Rules\KvkNumber;

function fout(object $rule, string $waarde): ?string
{
    $melding = null;

    $rule->validate('veld', $waarde, function (string $bericht) use (&$melding) {
        $melding ??= $bericht;
    });

    return $melding;
}

test('een geldig BTW-nummer wordt geaccepteerd', function (string $waarde) {
    expect(fout(new DutchVatNumber, $waarde))->toBeNull();
})->with(['NL123456789B01', 'nl123456789b01', 'NL 123456789 B01', 'NL123456789B01 ']);

test('een BTW-nummer zonder NL zegt precies wat eraan moet', function () {
    expect(fout(new DutchVatNumber, '123456789B01'))
        ->toBe('Het BTW-nummer moet met NL beginnen. Vul NL123456789B01 in.');
});

test('een BTW-nummer benoemt de ontbrekende B en het aantal cijfers', function (string $waarde, string $verwacht) {
    expect(fout(new DutchVatNumber, $waarde))->toContain($verwacht);
})->with([
    ['NL12345678901', 'ontbreekt de B'],
    ['NL12345B01', 'negen cijfers te staan, u vulde er 5 in'],
    ['NL123456789B1', 'twee cijfers te staan, u vulde er 1 in'],
    ['BE123456789B01', 'moet met NL beginnen'],
]);

test('een IBAN met spaties of kleine letters wordt geaccepteerd', function (string $waarde) {
    expect(fout(new DutchIban, $waarde))->toBeNull();
})->with(['NL91ABNA0417164300', 'NL91 ABNA 0417 1643 00', 'nl91abna0417164300']);

test('een IBAN benoemt het land en de lengte', function (string $waarde, string $verwacht) {
    expect(fout(new DutchIban, $waarde))->toContain($verwacht);
})->with([
    ['BE68539007547034', 'moet met NL beginnen'],
    ['NL91ABNA04171643', 'bestaat uit 18 tekens, u vulde er 16 in'],
]);

test('een KvK-nummer benoemt letters en het aantal cijfers', function (string $waarde, string $verwacht) {
    expect(fout(new KvkNumber, $waarde))->toContain($verwacht);
})->with([
    ['1234567A', 'alleen cijfers'],
    ['1234567', 'bestaat uit 8 cijfers, u vulde er 7 in'],
    ['123456789', 'bestaat uit 8 cijfers, u vulde er 9 in'],
]);

test('een KvK-nummer met punten of spaties wordt geaccepteerd', function () {
    expect(fout(new KvkNumber, '1234 5678'))->toBeNull();
});

test('een postcode zonder letters vraagt om de letters', function () {
    expect(fout(new DutchPostalCode, '1234'))
        ->toBe('Vul ook de twee letters van de postcode in, bijvoorbeeld 1234 AB.');
});

test('een postcode wordt in elke schrijfwijze geaccepteerd', function (string $waarde) {
    expect(fout(new DutchPostalCode, $waarde))->toBeNull();
})->with(['1234AB', '1234 ab', '1234-AB']);

test('een telefoonnummer benoemt het aantal cijfers en de opmaak', function (string $waarde, string $verwacht) {
    expect(fout(new DutchPhoneNumber, $waarde))->toContain($verwacht);
})->with([
    ['0612345', '10 cijfers, u vulde er 7 in'],
    ['612345678', 'moet met 0 of +31 beginnen'],
    ['06-1234abcd', 'alleen cijfers'],
]);

test('een telefoonnummer wordt in elke gangbare schrijfwijze geaccepteerd', function (string $waarde) {
    expect(fout(new DutchPhoneNumber, $waarde))->toBeNull();
})->with(['06-12345678', '0612345678', '+31612345678', '+31 6 12345678', '010-1234567', '(010) 1234567']);
