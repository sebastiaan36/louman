<?php

use App\Jobs\DeliverWebhook;
use App\Models\ApiClient;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatcher;
use App\Support\WebhookEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function dispatchPing(array $data = ['message' => 'hallo']): array
{
    return app(WebhookDispatcher::class)->dispatch(WebhookEvent::Ping, $data);
}

it('queues a delivery for every subscribed endpoint', function () {
    Queue::fake();

    WebhookEndpoint::factory()->subscribedTo([WebhookEvent::Ping])->create();
    WebhookEndpoint::factory()->subscribedTo([WebhookEvent::Ping])->create();
    WebhookEndpoint::factory()->subscribedTo([WebhookEvent::OrderCreated])->create();

    $deliveries = dispatchPing();

    expect($deliveries)->toHaveCount(2);
    Queue::assertPushed(DeliverWebhook::class, 2);
});

it('skips endpoints that are switched off', function () {
    Queue::fake();

    WebhookEndpoint::factory()->inactive()->subscribedTo([WebhookEvent::Ping])->create();

    expect(dispatchPing())->toHaveCount(0);
    Queue::assertNothingPushed();
});

it('skips endpoints of a deactivated integration', function () {
    Queue::fake();

    $client = ApiClient::factory()->inactive()->create();
    WebhookEndpoint::factory()->for($client, 'apiClient')->subscribedTo([WebhookEvent::Ping])->create();

    expect(dispatchPing())->toHaveCount(0);
});

it('signs the delivery so the receiver can verify it came from us', function () {
    Http::fake(['*' => Http::response('ok', 200)]);

    $endpoint = WebhookEndpoint::factory()->create(['secret' => 'geheim-123']);
    $delivery = WebhookDelivery::create([
        'webhook_endpoint_id' => $endpoint->id,
        'uuid' => 'de1iver-uuid',
        'event' => WebhookEvent::Ping,
        'payload' => ['event' => WebhookEvent::Ping, 'data' => ['message' => 'hallo']],
    ]);

    (new DeliverWebhook($delivery))->handle();

    Http::assertSent(function ($request) {
        $timestamp = $request->header('X-Louman-Timestamp')[0];
        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->body(), 'geheim-123');

        return $request->header('X-Louman-Signature')[0] === $expected
            && $request->header('X-Louman-Event')[0] === WebhookEvent::Ping
            && $request->header('X-Louman-Delivery')[0] === 'de1iver-uuid';
    });

    expect($delivery->fresh())
        ->delivered_at->not->toBeNull()
        ->response_status->toBe(200)
        ->attempts->toBe(1);
});

it('throws so the queue retries when the receiver answers with an error', function () {
    Http::fake(['*' => Http::response('kapot', 500)]);

    $endpoint = WebhookEndpoint::factory()->create();
    $delivery = WebhookDelivery::create([
        'webhook_endpoint_id' => $endpoint->id,
        'uuid' => 'de1iver-uuid',
        'event' => WebhookEvent::Ping,
        'payload' => ['event' => WebhookEvent::Ping],
    ]);

    expect(fn () => (new DeliverWebhook($delivery))->handle())
        ->toThrow(RuntimeException::class);

    expect($delivery->fresh())
        ->delivered_at->toBeNull()
        ->failed_at->toBeNull()
        ->response_status->toBe(500)
        ->response_body->toBe('kapot');
});

it('marks the delivery failed after the last attempt', function () {
    $endpoint = WebhookEndpoint::factory()->create();
    $delivery = WebhookDelivery::create([
        'webhook_endpoint_id' => $endpoint->id,
        'uuid' => 'de1iver-uuid',
        'event' => WebhookEvent::Ping,
        'payload' => ['event' => WebhookEvent::Ping],
    ]);

    (new DeliverWebhook($delivery))->failed(new RuntimeException('onbereikbaar'));

    expect($delivery->fresh())
        ->failed_at->not->toBeNull()
        ->error->toBe('onbereikbaar');
});

it('does not deliver twice when the job runs again after success', function () {
    Http::fake(['*' => Http::response('ok', 200)]);

    $endpoint = WebhookEndpoint::factory()->create();
    $delivery = WebhookDelivery::create([
        'webhook_endpoint_id' => $endpoint->id,
        'uuid' => 'de1iver-uuid',
        'event' => WebhookEvent::Ping,
        'payload' => ['event' => WebhookEvent::Ping],
        'delivered_at' => now(),
    ]);

    (new DeliverWebhook($delivery))->handle();

    Http::assertNothingSent();
});

it('gives up when the endpoint was switched off before the job ran', function () {
    Http::fake();

    $endpoint = WebhookEndpoint::factory()->inactive()->create();
    $delivery = WebhookDelivery::create([
        'webhook_endpoint_id' => $endpoint->id,
        'uuid' => 'de1iver-uuid',
        'event' => WebhookEvent::Ping,
        'payload' => ['event' => WebhookEvent::Ping],
    ]);

    (new DeliverWebhook($delivery))->handle();

    Http::assertNothingSent();
    expect($delivery->fresh()->failed_at)->not->toBeNull();
});

it('wraps the event in an envelope with event, timestamp and data', function () {
    Queue::fake();

    WebhookEndpoint::factory()->subscribedTo([WebhookEvent::Ping])->create();

    $delivery = dispatchPing(['message' => 'hallo'])[0];

    expect($delivery->payload)
        ->toHaveKeys(['event', 'occurred_at', 'data'])
        ->and($delivery->payload['event'])->toBe(WebhookEvent::Ping)
        ->and($delivery->payload['data'])->toBe(['message' => 'hallo']);
});
