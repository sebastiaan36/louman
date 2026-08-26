<?php

namespace App\Console\Commands;

use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use Illuminate\Console\Command;

/**
 * Requeues deliveries that gave up after all their retries, for when the
 * receiver was down longer than the retry window covers.
 */
class RetryWebhookDeliveries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:webhook:retry
        {--endpoint= : Alleen deliveries van dit endpoint-id}
        {--event= : Alleen deliveries van dit event}
        {--since= : Alleen deliveries die na dit moment zijn mislukt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Zet mislukte webhook-deliveries opnieuw in de wachtrij';

    public function handle(): int
    {
        $query = WebhookDelivery::query()->failed();

        if ($endpointId = $this->option('endpoint')) {
            $query->where('webhook_endpoint_id', $endpointId);
        }

        if ($event = $this->option('event')) {
            $query->where('event', $event);
        }

        if ($since = $this->option('since')) {
            $query->where('failed_at', '>=', now()->parse($since));
        }

        $deliveries = $query->orderBy('created_at')->get();

        if ($deliveries->isEmpty()) {
            $this->components->info('Geen mislukte deliveries gevonden.');

            return self::SUCCESS;
        }

        foreach ($deliveries as $delivery) {
            $delivery->update([
                'failed_at' => null,
                'error' => null,
                'attempts' => 0,
            ]);

            DeliverWebhook::dispatch($delivery);
        }

        $count = $deliveries->count();

        $this->components->info("{$count} ".($count === 1 ? 'delivery' : 'deliveries').' opnieuw in de wachtrij gezet.');

        return self::SUCCESS;
    }
}
