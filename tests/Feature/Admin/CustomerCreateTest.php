<?php

use App\Mail\CustomerInvitation;
use App\Models\Customer;
use App\Models\CustomerInvitation as CustomerInvitationModel;
use Illuminate\Support\Facades\Mail;

test('admin kan klant aanmaken met alleen bedrijfsnaam', function () {
    Mail::fake();
    $admin = adminUser();

    $this->actingAs($admin)
        ->post('/admin/customers', ['company_name' => 'Nieuwe Klant BV'])
        ->assertRedirect();

    $customer = Customer::where('company_name', 'Nieuwe Klant BV')->first();
    expect($customer)->not->toBeNull();
    expect($customer->user_id)->toBeNull();
    expect($customer->approved_at)->not->toBeNull();
    expect($customer->approved_by)->toBe($admin->id);
    expect(CustomerInvitationModel::count())->toBe(0);
    Mail::assertNothingSent();
});

test('admin kan klant aanmaken met email en uitnodiging wordt verstuurd', function () {
    Mail::fake();
    $admin = adminUser();

    $this->actingAs($admin)
        ->post('/admin/customers', [
            'company_name' => 'Nieuwe Klant BV',
            'email' => 'klant@example.com',
        ])
        ->assertRedirect();

    $invitation = CustomerInvitationModel::first();
    expect($invitation)->not->toBeNull();
    expect($invitation->email)->toBe('klant@example.com');
    expect($invitation->expires_at)->toBeInstanceOf(\DateTimeInterface::class);
    expect($invitation->expires_at->isFuture())->toBeTrue();

    Mail::assertSent(CustomerInvitation::class, fn ($mail) => $mail->hasTo('klant@example.com'));
});

test('admin mag geen klant aanmaken zonder bedrijfsnaam', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post('/admin/customers', ['company_name' => ''])
        ->assertSessionHasErrors('company_name');
});

test('niet-admin mag geen klant aanmaken', function () {
    $user = customerUser();

    $this->actingAs($user)
        ->post('/admin/customers', ['company_name' => 'Hack BV'])
        ->assertForbidden();
});

test('duplicate email wordt geweigerd', function () {
    Mail::fake();
    $admin = adminUser();
    customerUser(); // schept een user met willekeurig email; we maken zelf één met bekend email
    \App\Models\User::factory()->create(['email' => 'bestaand@example.com']);

    $this->actingAs($admin)
        ->post('/admin/customers', [
            'company_name' => 'Nieuwe BV',
            'email' => 'bestaand@example.com',
        ])
        ->assertSessionHasErrors('email');
});

test('admin kan alsnog een uitnodiging sturen naar klant zonder account', function () {
    Mail::fake();
    $admin = adminUser();
    $customer = Customer::factory()->create(['user_id' => null]);

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/invite", [
            'email' => 'nieuw@klant.nl',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(CustomerInvitationModel::where('customer_id', $customer->id)->where('email', 'nieuw@klant.nl')->exists())->toBeTrue();
    Mail::assertSent(CustomerInvitation::class);
});

test('uitnodiging vervangt eerdere openstaande uitnodiging', function () {
    Mail::fake();
    $admin = adminUser();
    $customer = Customer::factory()->create(['user_id' => null]);
    CustomerInvitationModel::create([
        'customer_id' => $customer->id,
        'email' => 'oud@klant.nl',
        'token' => hash('sha256', 'oud-token'),
        'expires_at' => now()->addDays(30),
    ]);

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/invite", [
            'email' => 'nieuw@klant.nl',
        ])
        ->assertSessionHas('success');

    expect(CustomerInvitationModel::where('customer_id', $customer->id)->count())->toBe(1);
    expect(CustomerInvitationModel::where('customer_id', $customer->id)->first()->email)->toBe('nieuw@klant.nl');
});

test('klant met bestaand account kan niet opnieuw uitgenodigd worden', function () {
    Mail::fake();
    $admin = adminUser();
    $customer = approvedCustomer();

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/invite", [
            'email' => 'iets@klant.nl',
        ])
        ->assertSessionHas('error');

    Mail::assertNothingSent();
});

test('uitnodiging vereist een uniek e-mailadres', function () {
    Mail::fake();
    $admin = adminUser();
    $customer = Customer::factory()->create(['user_id' => null]);
    \App\Models\User::factory()->create(['email' => 'bestaand@klant.nl']);

    $this->actingAs($admin)
        ->post("/admin/customers/{$customer->id}/invite", [
            'email' => 'bestaand@klant.nl',
        ])
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});

test('uitnodigingsmail bevat het logo via een publieke url', function () {
    $customer = Customer::factory()->create(['user_id' => null]);
    $invitation = CustomerInvitationModel::create([
        'customer_id' => $customer->id,
        'email' => 'klant@example.com',
        'token' => hash('sha256', 'raw-token'),
        'expires_at' => now()->addDays(30),
    ]);

    $rendered = (new CustomerInvitation($invitation, 'raw-token'))->render();

    expect($rendered)->toContain('storage/img/Logo.png');
    expect($rendered)->not->toContain('data:image/png;base64');
});
