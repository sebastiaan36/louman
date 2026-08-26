<?php

namespace App\Console\Commands;

use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Support\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Sends a test event so the receiver can verify its signature check before any
 * real data flows.
 */
class PingWebhookEndpoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:webhook:ping
        {endpoint : Id van het webhook-endpoint}
        {--sync : Nu versturen in plaats van via de wachtrij}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stuur een testbericht naar een webhook-endpoint';

    public function handle(): int
    {
        $endpoint = WebhookEndpoint::find($this->argument('endpoint'));

        if ($endpoint === null) {
            $this->components->error('Onbekend endpoint.');

            return self::FAILURE;
        }

        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'uuid' => (string) Str::uuid(),
            'event' => WebhookEvent::Ping,
            'payload' => [
                'event' => WebhookEvent::Ping,
                'occurred_at' => now()->toIso8601String(),
                'data' => ['message' => 'Testbericht vanuit het Louman-portaal.'],
            ],
        ]);

        if ($this->option('sync')) {
            (new DeliverWebhook($delivery))->handle();

            $delivery->refresh();

            if ($delivery->delivered_at !== null) {
                $this->components->info("Testbericht afgeleverd bij {$endpoint->url} (status {$delivery->response_status}).");

                return self::SUCCESS;
            }

            $this->components->error("Testbericht mislukt: {$delivery->error}");

            return self::FAILURE;
        }

        DeliverWebhook::dispatch($delivery);

        $this->components->info("Testbericht in de wachtrij gezet voor {$endpoint->url}.");
        $this->components->twoColumnDetail('Delivery', $delivery->uuid);

        return self::SUCCESS;
    }
}
