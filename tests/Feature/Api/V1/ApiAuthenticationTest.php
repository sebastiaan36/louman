<?php

use App\Models\ApiClient;
use App\Models\Product;
use App\Support\ApiAbility;

it('rejects a request without a token', function () {
    $this->getJson('/api/v1/products')->assertUnauthorized();
});

it('rejects a token from a deactivated integration', function () {
    $client = ApiClient::factory()->inactive()->create();

    $this->getJson('/api/v1/products', apiHeaders([ApiAbility::ProductsRead], $client))
        ->assertForbidden()
        ->assertJsonPath('message', 'Deze koppeling is gedeactiveerd.');
});

it('rejects a request from an IP outside the allowlist', function () {
    $client = ApiClient::factory()->restrictedToIps(['203.0.113.10'])->create();

    $this->getJson('/api/v1/products', apiHeaders([ApiAbility::ProductsRead], $client))
        ->assertForbidden()
        ->assertJsonPath('message', 'Dit IP-adres is niet toegestaan voor deze koppeling.');
});

it('accepts a request from an IP on the allowlist', function () {
    $client = ApiClient::factory()->restrictedToIps(['127.0.0.1'])->create();

    $this->getJson('/api/v1/products', apiHeaders([ApiAbility::ProductsRead], $client))
        ->assertOk();
});

it('rejects a token that lacks the required ability', function () {
    Product::factory()->create(['article_number' => 'ART-1']);

    $this->getJson('/api/v1/orders', apiHeaders([ApiAbility::ProductsRead]))
        ->assertForbidden();
});

it('records when the integration was last used', function () {
    $client = ApiClient::factory()->create();

    expect($client->last_used_at)->toBeNull();

    $this->getJson('/api/v1/products', apiHeaders([ApiAbility::ProductsRead], $client))->assertOk();

    expect($client->fresh()->last_used_at)->not->toBeNull();
});

it('requires an idempotency key on writes', function () {
    $headers = apiHeaders([ApiAbility::ProductsWrite]);
    unset($headers['Idempotency-Key']);

    $this->putJson('/api/v1/products/ART-1', productPayload(), $headers)
        ->assertStatus(400)
        ->assertJsonPath('message', 'Stuur een Idempotency-Key header mee bij schrijfacties.');
});

it('replays the stored response when the same idempotency key returns', function () {
    $headers = apiHeaders([ApiAbility::ProductsWrite], null, 'key-123');

    $first = $this->putJson('/api/v1/products/ART-1', productPayload(), $headers);
    $first->assertCreated();

    // The replay returns the original response verbatim, including its status.
    $second = $this->putJson('/api/v1/products/ART-1', productPayload(), $headers);
    $second->assertCreated()
        ->assertHeader('Idempotent-Replay', 'true')
        ->assertJsonPath('data.article_number', 'ART-1');

    expect(Product::count())->toBe(1);
});

it('refuses to reuse an idempotency key for a different payload', function () {
    $headers = apiHeaders([ApiAbility::ProductsWrite], null, 'key-123');

    $this->putJson('/api/v1/products/ART-1', productPayload(), $headers)->assertCreated();

    $this->putJson('/api/v1/products/ART-1', productPayload(['title' => 'Iets anders']), $headers)
        ->assertStatus(409);
});

it('lets a failed write be retried with the same idempotency key', function () {
    $headers = apiHeaders([ApiAbility::ProductsWrite], null, 'key-123');

    $this->putJson('/api/v1/products/ART-1', productPayload(['price' => -5]), $headers)
        ->assertStatus(422);

    $this->putJson('/api/v1/products/ART-1', productPayload(), $headers)
        ->assertCreated();
});

it('rejects unknown fields instead of ignoring them', function () {
    $this->putJson(
        '/api/v1/products/ART-1',
        productPayload(['prijs_incl_btw' => 12.50]),
        apiHeaders([ApiAbility::ProductsWrite]),
    )
        ->assertStatus(422)
        ->assertJsonValidationErrors('prijs_incl_btw');
});
