<?php

use App\Mail\CustomerInvitation as CustomerInvitationMail;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerInvitation;
use Illuminate\Support\Facades\Mail;

function invitedCustomer(string $email = 'klant@example.com', ?DateTimeInterface $expiresAt = null): array
{
    $customer = Customer::factory()->approved()->create([
        'user_id' => null,
        'company_name' => 'Bakkerij De Hoek',
    ]);

    $invitation = CustomerInvitation::create([
        'customer_id' => $customer->id,
        'email' => $email,
        'token' => hash('sha256', 'oud-token'),
        'expires_at' => $expiresAt ?? now()->addDays(30),
    ]);

    return [$customer, $invitation];
}

test('de uitnodiging gaat opnieuw naar hetzelfde adres', function () {
    Mail::fake();

    [$customer] = invitedCustomer('nieuw@klant.nl');

    $this->actingAs(adminUser())
        ->post("/admin/customers/{$customer->id}/invite/resend")
        ->assertRedirect()
        ->assertSessionHas('success', 'Uitnodiging opnieuw verstuurd naar nieuw@klant.nl.');

    Mail::assertSent(CustomerInvitationMail::class, fn ($mail) => $mail->hasTo('nieuw@klant.nl'));
});

test('de oude link werkt niet meer na opnieuw versturen', function () {
    Mail::fake();

    [$customer, $oud] = invitedCustomer();

    $this->actingAs(adminUser())
        ->post("/admin/customers/{$customer->id}/invite/resend")
        ->assertRedirect();

    expect(CustomerInvitation::find($oud->id))->toBeNull()
        ->and($customer->invitations()->count())->toBe(1);
});

test('een verlopen uitnodiging kan opnieuw worden verstuurd', function () {
    Mail::fake();

    [$customer] = invitedCustomer('klant@example.com', now()->subDay());

    $this->actingAs(adminUser())
        ->post("/admin/customers/{$customer->id}/invite/resend")
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($customer->pendingInvitation()->expires_at->isFuture())->toBeTrue();
});

test('opnieuw versturen kan niet als de klant al een account heeft', function () {
    Mail::fake();

    [$customer] = invitedCustomer();
    $customer->update(['user_id' => customerUser()->id]);

    $this->actingAs(adminUser())
        ->post("/admin/customers/{$customer->id}/invite/resend")
        ->assertRedirect()
        ->assertSessionHas('error', 'Deze klant heeft al een account.');

    Mail::assertNothingSent();
});

test('opnieuw versturen kan niet als er nooit een uitnodiging is verstuurd', function () {
    Mail::fake();

    $customer = Customer::factory()->approved()->create(['user_id' => null]);

    $this->actingAs(adminUser())
        ->post("/admin/customers/{$customer->id}/invite/resend")
        ->assertRedirect()
        ->assertSessionHas('error', 'Er is nog geen uitnodiging verstuurd naar deze klant.');

    Mail::assertNothingSent();
});

test('opnieuw versturen wordt vastgelegd in het auditlogboek', function () {
    Mail::fake();

    [$customer] = invitedCustomer('nieuw@klant.nl');

    $this->actingAs(adminUser())->post("/admin/customers/{$customer->id}/invite/resend");

    $log = AuditLog::where('action', 'customer.invited')->latest('id')->firstOrFail();

    expect($log->description)->toContain('opnieuw verstuurd naar nieuw@klant.nl')
        ->and($log->metadata['resent'])->toBeTrue();
});

test('een niet-admin kan geen uitnodiging opnieuw versturen', function () {
    [$customer] = invitedCustomer();

    $this->actingAs(approvedCustomer()->user)
        ->post("/admin/customers/{$customer->id}/invite/resend")
        ->assertForbidden();
});

test('de klantpagina toont de openstaande uitnodiging', function () {
    [$customer] = invitedCustomer('nieuw@klant.nl');

    $this->actingAs(adminUser())
        ->get("/admin/customers/{$customer->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('customer.pending_invitation.email', 'nieuw@klant.nl')
            ->where('customer.pending_invitation.is_expired', false)
        );
});

test('de klantpagina toont geen uitnodiging als de klant al een account heeft', function () {
    [$customer] = invitedCustomer();
    $customer->update(['user_id' => customerUser()->id]);

    $this->actingAs(adminUser())
        ->get("/admin/customers/{$customer->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('customer.pending_invitation', null));
});
