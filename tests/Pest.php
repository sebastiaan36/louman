<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
