<?php

namespace Database\Factories;

use App\Models\ApiClient;
use App\Models\WebhookEndpoint;
use App\Support\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookEndpoint>
 */
class WebhookEndpointFactory extends Factory
{
    protected $model = WebhookEndpoint::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'api_client_id' => ApiClient::factory(),
            'url' => 'https://pakket.example.test/webhooks/louman',
            'secret' => Str::random(64),
            'events' => WebhookEvent::ALL,
            'is_active' => true,
        ];
    }

    /**
     * Subscribe the endpoint to specific events only.
     *
     * @param  list<string>  $events
     */
    public function subscribedTo(array $events): static
    {
        return $this->state(fn (array $attributes): array => [
            'events' => $events,
        ]);
    }

    /**
     * Indicate that the endpoint has been switched off.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
