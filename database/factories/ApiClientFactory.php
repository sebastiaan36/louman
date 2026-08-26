<?php

namespace Database\Factories;

use App\Models\ApiClient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiClient>
 */
class ApiClientFactory extends Factory
{
    protected $model = ApiClient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' koppeling',
            'contact_email' => fake()->companyEmail(),
            'is_active' => true,
            'allowed_ips' => null,
        ];
    }

    /**
     * Indicate that the client has been deactivated.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Restrict the client to the given IP addresses.
     *
     * @param  list<string>  $ipAddresses
     */
    public function restrictedToIps(array $ipAddresses): static
    {
        return $this->state(fn (array $attributes): array => [
            'allowed_ips' => $ipAddresses,
        ]);
    }
}
