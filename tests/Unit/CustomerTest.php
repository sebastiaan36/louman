<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('isApproved geeft true voor goedgekeurde klant', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::factory()->approved()->create(['user_id' => $user->id]);

    expect($customer->isApproved())->toBeTrue();
});

test('isApproved geeft false voor wachtende klant', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::factory()->pending()->create(['user_id' => $user->id]);

    expect($customer->isApproved())->toBeFalse();
});

test('getCategoryLabel geeft juist label voor groothandel', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'customer_category' => 'groothandel',
    ]);

    expect($customer->getCategoryLabel())->toBe('Groothandel');
});

test('getCategoryLabel geeft juist label voor broodjeszaak', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'customer_category' => 'broodjeszaak',
    ]);

    expect($customer->getCategoryLabel())->toBe('Broodjeszaak');
});

test('getCategoryLabel geeft juist label voor horeca', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'customer_category' => 'horeca',
    ]);

    expect($customer->getCategoryLabel())->toBe('Horeca');
});

test('getCategoryLabel geeft null voor onbekende categorie', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'customer_category' => null,
    ]);

    expect($customer->getCategoryLabel())->toBeNull();
});
