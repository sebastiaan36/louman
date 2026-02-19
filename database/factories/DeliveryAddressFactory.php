<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\DeliveryAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryAddress>
 */
class DeliveryAddressFactory extends Factory
{
    protected $model = DeliveryAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory()->approved(),
            'name' => fake()->company() . ' - Magazijn',
            'street_name' => fake()->streetName(),
            'house_number' => (string) fake()->numberBetween(1, 200),
            'postal_code' => '1234 AB',
            'city' => fake()->city(),
            'notes' => null,
            'is_default' => false,
        ];
    }
}
