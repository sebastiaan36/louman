<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone_number' => fake()->phoneNumber(),
            'street_name' => fake()->streetName(),
            'house_number' => (string) fake()->numberBetween(1, 200),
            'postal_code' => '1234 AB',
            'city' => fake()->city(),
            'kvk_number' => fake()->numerify('########'),
            'vat_number' => 'NL' . fake()->numerify('#########') . 'B01',
            'bank_account' => 'NL' . fake()->numerify('##') . 'TEST' . fake()->numerify('##########'),
            'packing_slip_email' => null,
            'approved_at' => null,
            'approved_by' => null,
            'customer_category' => null,
            'discount_percentage' => 0,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_at' => now(),
            'customer_category' => 'groothandel',
            'discount_percentage' => 0,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_at' => null,
            'customer_category' => null,
        ]);
    }
}
