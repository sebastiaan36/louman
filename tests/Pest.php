<?php

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\User;
use App\Support\ApiAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function customerUser(): User
{
    return User::factory()->create(['role' => 'customer']);
}

function approvedCustomer(?User $user = null): Customer
{
    $user ??= customerUser();

    return Customer::factory()->approved()->create(['user_id' => $user->id]);
}

function pendingCustomer(?User $user = null): Customer
{
    $user ??= customerUser();

    return Customer::factory()->pending()->create(['user_id' => $user->id]);
}

/**
 * Create an integration client and issue it a real token. Returns the headers
 * an external system would send, so tests exercise the full middleware chain
 * instead of bypassing it.
 *
 * @param  list<string>  $abilities
 * @return array<string, string>
 */
function apiHeaders(array $abilities = ApiAbility::ALL, ?ApiClient $client = null, ?string $idempotencyKey = null): array
{
    $client ??= ApiClient::factory()->create();

    $token = $client->createToken('test', $abilities)->plainTextToken;

    return array_filter([
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'Idempotency-Key' => $idempotencyKey ?? (string) Str::uuid(),
    ]);
}

/**
 * A valid article payload for the upsert endpoint, with room to override or
 * add fields per test.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function productPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Kaasbroodje',
        'description' => 'Vers gebakken kaasbroodje.',
        'price' => 1.25,
        'in_stock' => true,
        'is_active' => true,
    ], $overrides);
}
