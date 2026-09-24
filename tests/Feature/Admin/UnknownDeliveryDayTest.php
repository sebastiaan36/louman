<?php

use App\Models\Customer;
use App\Support\DeliveryDay;
use Illuminate\Support\Facades\Notification;

test('een klant kan worden goedgekeurd zonder leverdag, zodat die later bepaald wordt', function () {
    Notification::fake();
    $customer = pendingCustomer();

    $this->actingAs(adminUser())
        ->post("/admin/customers/{$customer->id}/approve", [
            'customer_category' => 'horeca',
            'delivery_day' => null,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($customer->fresh()->isApproved())->toBeTrue()
        ->and($customer->fresh()->delivery_day)->toBeNull();
});

test('een leverdag die geen echte dag is wordt nog steeds geweigerd', function () {
    $customer = pendingCustomer();

    $this->actingAs(adminUser())
        ->from('/admin/customers/pending')
        ->post("/admin/customers/{$customer->id}/approve", [
            'customer_category' => 'horeca',
            'delivery_day' => 'zaterdag',
        ])
        ->assertSessionHasErrors('delivery_day');

    expect($customer->fresh()->isApproved())->toBeFalse();
});

test('de leverdag kan later in de klantinstellingen worden ingevuld of weer op niet bekend gezet', function () {
    $customer = Customer::factory()->approved()->create(['delivery_day' => null]);
    $payload = ['customer_category' => 'horeca', 'discount_percentage' => '', 'show_on_map' => true];

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/category-discount", $payload + ['delivery_day' => 'woensdag'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    expect($customer->fresh()->delivery_day)->toBe('woensdag');

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/category-discount", $payload + ['delivery_day' => null])
        ->assertSessionHasNoErrors();
    expect($customer->fresh()->delivery_day)->toBeNull();
});

test('een klant zonder leverdag staat op de rijroute nergens en op het klantdetail als niet bekend', function () {
    Customer::factory()->approved()->create(['delivery_day' => null]);

    $this->actingAs(adminUser())
        ->get('/admin/delivery-route?day=all')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('dayGroups', fn ($groups) => collect($groups)->sum(fn ($g) => count($g['customers'])) === 0));

    expect(file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CustomerDetail.vue'))
        ->toContain("customer.delivery_day ?? 'Niet bekend'");
});

test('beide dagkeuzes hebben de optie Niet bekend en sturen dan null', function () {
    $root = dirname(__DIR__, 3);

    foreach (['PendingCustomers', 'CustomerDetail'] as $page) {
        expect(file_get_contents($root."/resources/js/pages/admin/{$page}.vue"))
            ->toContain('<option :value="DELIVERY_DAY_UNKNOWN">{{ DELIVERY_DAY_UNKNOWN_LABEL }} (later bepalen)</option>')
            ->toContain('=== DELIVERY_DAY_UNKNOWN ? null :');
    }

    // "onbekend" is geen leverdag en mag dus nooit in de serverlijst terechtkomen.
    expect(DeliveryDay::ALL)->not->toContain('onbekend');
});
