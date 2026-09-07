<?php

/**
 * Een beheerder die per ongeluk op een klantpagina belandt hoort naar het
 * dashboard te worden gestuurd. De middleware verwees naar een routenaam die
 * niet bestaat, waardoor dat een 500 opleverde in plaats van een omleiding.
 */
test('een beheerder op een klantpagina wordt naar het dashboard gestuurd', function (string $pad) {
    $this->actingAs(adminUser())
        ->get($pad)
        ->assertRedirect(route('dashboard'));
})->with([
    '/customer/products',
    '/customer/cart',
    '/customer/orders',
    '/customer/favorites',
]);
