<?php

use App\Models\ApiClient;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Support\ApiAbility;
use App\Support\WebhookEvent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Mail::fake();
});

it('announces an order placed by a customer', function () {
    WebhookEndpoint::factory()->subscribedTo([WebhookEvent::OrderCreated])->create();

    $user = customerUser();
    $customer = approvedCustomer($user);
    $product = Product::factory()->create(['article_number' => 'ART-1', 'price' => 2.50]);

    CartItem::create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $this->actingAs($user)
        ->post(route('customer.orders.store'), ['notes' => 'Graag voor 10 uur'])
        ->assertSessionHasNoErrors();

    $delivery = WebhookDelivery::where('event', WebhookEvent::OrderCreated)->firstOrFail();

    expect($delivery->payload['data']['notes'])->toBe('Graag voor 10 uur')
        ->and($delivery->payload['data']['items'][0]['article_number'])->toBe('ART-1')
        ->and($delivery->payload['data']['items'][0]['quantity'])->toBe(3);
});

it('announces a status change made in the portal', function () {
    WebhookEndpoint::factory()->subscribedTo([WebhookEvent::OrderStatusChanged])->create();

    $order = Order::factory()->pending()->create();

    $this->actingAs(adminUser())
        ->patch(route('admin.orders.update-status', $order), ['status' => 'confirmed'])
        ->assertSessionHasNoErrors();

    $delivery = WebhookDelivery::where('event', WebhookEvent::OrderStatusChanged)->firstOrFail();

    expect($delivery->payload['data']['status'])->toBe('confirmed')
        ->and($delivery->payload['data']['previous_status'])->toBe('pending');
});

it('does not send a status change back to the integration that made it', function () {
    $client = ApiClient::factory()->create();
    WebhookEndpoint::factory()->for($client, 'apiClient')
        ->subscribedTo([WebhookEvent::OrderStatusChanged])
        ->create();

    $order = Order::factory()->confirmed()->create();

    $this->patchJson("/api/v1/orders/{$order->id}/status", [
        'status' => 'completed',
    ], apiHeaders([ApiAbility::OrdersWrite], $client))->assertOk();

    expect(WebhookDelivery::count())->toBe(0);
});

it('still sends a status change to the other integrations', function () {
    $actingClient = ApiClient::factory()->create();
    WebhookEndpoint::factory()->for($actingClient, 'apiClient')
        ->subscribedTo([WebhookEvent::OrderStatusChanged])
        ->create();
    WebhookEndpoint::factory()->subscribedTo([WebhookEvent::OrderStatusChanged])->create();

    $order = Order::factory()->confirmed()->create();

    $this->patchJson("/api/v1/orders/{$order->id}/status", [
        'status' => 'completed',
    ], apiHeaders([ApiAbility::OrdersWrite], $actingClient))->assertOk();

    expect(WebhookDelivery::count())->toBe(1);
});

it('announces a new registration', function () {
    Notification::fake();
    WebhookEndpoint::factory()->subscribedTo([WebhookEvent::CustomerRegistered])->create();

    $this->post(route('customer.register.store'), [
        'company_name' => 'Bakkerij De Hoek',
        'contact_person' => 'Sam de Vries',
        'email' => 'sam@dehoek.test',
        'password' => 'Wachtwoord!2026',
        'password_confirmation' => 'Wachtwoord!2026',
        'phone_number' => '0301234567',
        'street_name' => 'Hoofdstraat',
        'house_number' => '12',
        'postal_code' => '1234 AB',
        'city' => 'Utrecht',
        'kvk_number' => '12345678',
        'bank_account' => 'NL91ABNA0417164300',
        'vat_number' => 'NL123456789B01',
        'terms_accepted' => true,
    ])->assertSessionHasNoErrors();

    $delivery = WebhookDelivery::where('event', WebhookEvent::CustomerRegistered)->firstOrFail();

    expect($delivery->payload['data']['company_name'])->toBe('Bakkerij De Hoek')
        ->and($delivery->payload['data']['is_approved'])->toBeFalse()
        ->and($delivery->payload['data']['customer_number'])->toBeNull();
});

it('announces an approval', function () {
    Notification::fake();
    WebhookEndpoint::factory()->subscribedTo([WebhookEvent::CustomerApproved])->create();

    $customer = pendingCustomer();

    $this->actingAs(adminUser())
        ->post(route('admin.customers.approve', $customer), [
            'customer_category' => 'horeca',
            'delivery_day' => 'dinsdag',
            'discount_percentage' => '3',
        ])->assertSessionHasNoErrors();

    $delivery = WebhookDelivery::where('event', WebhookEvent::CustomerApproved)->firstOrFail();

    expect($delivery->payload['data']['is_approved'])->toBeTrue()
        ->and($delivery->payload['data']['customer_category'])->toBe('horeca')
        ->and($delivery->payload['data']['discount_percentage'])->toBe('3');
});

it('does not queue anything when nobody is subscribed', function () {
    WebhookEndpoint::factory()->subscribedTo([WebhookEvent::CustomerApproved])->create();

    Order::factory()->pending()->create();

    expect(WebhookDelivery::count())->toBe(0);
});
