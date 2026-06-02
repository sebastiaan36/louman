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
