<?php

use App\Models\User;

test('admin ziet de lijst met beheerders', function () {
    $admin = adminUser();
    User::factory()->create(['role' => 'admin', 'name' => 'Tweede Beheerder']);

    $this->actingAs($admin)
        ->get('/admin/administrators')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Administrators')
            ->has('administrators', 2)
        );
});

test('admin kan een nieuwe beheerder aanmaken', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post('/admin/administrators', [
            'name' => 'Nieuwe Beheerder',
            'email' => 'nieuw@louman.nl',
            'password' => 'Wachtwoord123!',
            'password_confirmation' => 'Wachtwoord123!',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $user = User::where('email', 'nieuw@louman.nl')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('admin');
    expect($user->hasVerifiedEmail())->toBeTrue();
});

test('beheerder aanmaken vereist een uniek e-mailadres', function () {
    $admin = adminUser();
    User::factory()->create(['email' => 'bestaand@louman.nl']);

    $this->actingAs($admin)
        ->post('/admin/administrators', [
            'name' => 'Iemand',
            'email' => 'bestaand@louman.nl',
            'password' => 'Wachtwoord123!',
            'password_confirmation' => 'Wachtwoord123!',
        ])
        ->assertSessionHasErrors('email');
});

test('admin kan een andere beheerder verwijderen', function () {
    $admin = adminUser();
    $other = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->delete("/admin/administrators/{$other->id}")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(User::find($other->id))->toBeNull();
});

test('admin kan zichzelf niet verwijderen', function () {
    $admin = adminUser();
    User::factory()->create(['role' => 'admin']); // zorgt dat er meer dan één admin is

    $this->actingAs($admin)
        ->delete("/admin/administrators/{$admin->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(User::find($admin->id))->not->toBeNull();
});

test('de laatste beheerder kan niet verwijderd worden', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->delete("/admin/administrators/{$admin->id}")
        ->assertSessionHas('error');

    expect(User::where('role', 'admin')->count())->toBe(1);
});

test('een klant-account kan niet via deze route verwijderd worden', function () {
    $admin = adminUser();
    $customer = approvedCustomer();

    $this->actingAs($admin)
        ->delete("/admin/administrators/{$customer->user->id}")
        ->assertNotFound();

    expect(User::find($customer->user->id))->not->toBeNull();
});

test('niet-admin heeft geen toegang tot beheerders', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->get('/admin/administrators')
        ->assertForbidden();
});
