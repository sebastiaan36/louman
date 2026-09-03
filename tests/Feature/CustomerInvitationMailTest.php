<?php

use App\Mail\CustomerInvitation;
use App\Models\Customer;
use App\Models\CustomerInvitation as CustomerInvitationModel;

function invitationMailHtml(): string
{
    $customer = Customer::factory()->create(['company_name' => 'Bakkerij De Hoek']);

    $invitation = CustomerInvitationModel::create([
        'customer_id' => $customer->id,
        'email' => 'contact@dehoek.test',
        'token' => hash('sha256', 'ruwe-token'),
        'expires_at' => now()->addDays(30),
    ]);

    return (new CustomerInvitation($invitation, 'ruwe-token'))->render();
}

test('de uitnodigingsmail opent met de nieuwe kop', function () {
    $html = invitationMailHtml();

    expect($html)
        ->toContain('Nieuw bij Slagerij Louman')
        ->toContain('Uw bestellingen voortaan eenvoudig online doorgeven')
        ->not->toContain('Welkom bij Slagerij Louman');
});

test('de uitnodigingsmail bevat de aankondiging van de nieuwe website en het portaal', function () {
    $html = invitationMailHtml();

    expect($html)
        ->toContain('Beste relatie')
        ->toContain('de nieuwe website van Slagerij Louman live staat')
        ->toContain('het nieuwe B2B Klantportaal');
});

test('de uitnodigingsmail noemt wat er via het portaal kan', function () {
    $html = invitationMailHtml();

    expect($html)
        ->toContain('Online uw wekelijkse of maandelijkse bestellingen plaatsen')
        ->toContain('Aangeven of u zichtbaar wilt zijn als officieel verkooppunt');
});

test('de uitnodigingsmail herhaalt de prijsafspraak en wat er niet verandert', function () {
    $html = invitationMailHtml();

    expect($html)
        ->toContain('Belangrijk om te weten')
        ->toContain('De getoonde prijzen in het klantportaal zijn onze standaardprijzen')
        ->toContain('Uw huidige prijsafspraken blijven ongewijzigd')
        ->toContain('Leverdagen en levertijden blijven hetzelfde')
        ->toContain('Facturatie blijft verlopen zoals u gewend bent');
});

test('de uitnodigingsmail staat volledig in de u-vorm', function () {
    $html = invitationMailHtml();

    // Alleen de leesbare tekst; klassenamen en attributen tellen niet mee.
    $text = preg_replace('/\s+/', ' ', strip_tags($html));

    expect($text)
        ->toContain('Wij hebben reeds een account voor u aangemaakt')
        ->toContain('uw registratie af te ronden')
        ->not->toMatch('/\b(je|jij|jouw|jullie)\b/i');
});

test('de uitnodigingsmail sluit af namens het team', function () {
    expect(invitationMailHtml())
        ->toContain('Met vriendelijke groet')
        ->toContain('Team Slagerij Louman');
});

test('de knop en de vervaldatum staan nog in de uitnodigingsmail', function () {
    $html = invitationMailHtml();

    expect($html)
        ->toContain('Account aanmaken')
        ->toContain('/invitation/ruwe-token')
        ->toContain('Deze uitnodiging is geldig tot');
});
