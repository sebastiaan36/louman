<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use App\Models\WebhookEndpoint;
use App\Support\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Registers a URL at the external package to push events to. The signing
 * secret is generated here and shown once; the receiver needs it to verify
 * that a delivery really came from us.
 */
class RegisterWebhookEndpoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:webhook
        {--client= : Naam van de koppeling waar dit endpoint bij hoort}
        {--url= : De https-URL waar events naartoe gestuurd worden}
        {--events= : Komma-gescheiden events, of "all" voor alle events}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registreer een webhook-endpoint waar het portaal events naartoe stuurt';

    public function handle(): int
    {
        $client = $this->resolveClient();

        if ($client === null) {
            return self::FAILURE;
        }

        $url = $this->option('url') ?: text(
            label: 'Naar welke URL sturen we de events?',
            placeholder: 'https://pakket.example.nl/webhooks/louman',
            required: true,
        );

        if (! str_starts_with($url, 'https://')) {
            $this->components->error('Een webhook-URL moet met https:// beginnen.');

            return self::FAILURE;
        }

        $events = $this->resolveEvents();

        if ($events === []) {
            return self::FAILURE;
        }

        $secret = Str::random(64);

        $endpoint = WebhookEndpoint::create([
            'api_client_id' => $client->id,
            'url' => $url,
            'secret' => $secret,
            'events' => $events,
            'is_active' => true,
        ]);

        $this->newLine();
        $this->components->info("Endpoint #{$endpoint->id} geregistreerd voor koppeling '{$client->name}'.");
        $this->components->twoColumnDetail('URL', $url);
        $this->components->twoColumnDetail('Events', implode(', ', $events));
        $this->newLine();
        $this->components->warn('Deel deze ondertekeningssleutel met de leverancier — hij wordt hierna niet meer getoond:');
        $this->line('  '.$secret);
        $this->newLine();
        $this->line('  De handtekening zit in de header X-Louman-Signature als "sha256=<hex>",');
        $this->line('  berekend als HMAC-SHA256 over "<X-Louman-Timestamp>.<body>" met deze sleutel.');
        $this->newLine();
        $this->components->info("Test de koppeling met: php artisan api:webhook:ping {$endpoint->id}");

        return self::SUCCESS;
    }

    /**
     * Find the integration this endpoint belongs to.
     */
    private function resolveClient(): ?ApiClient
    {
        $name = $this->option('client');

        if ($name) {
            $client = ApiClient::where('name', $name)->first();

            if ($client === null) {
                $this->components->error("Onbekende koppeling '{$name}'. Maak er eerst een token voor aan met api:token.");
            }

            return $client;
        }

        $clients = ApiClient::orderBy('name')->pluck('name', 'id')->all();

        if ($clients === []) {
            $this->components->error('Er is nog geen koppeling. Maak er eerst een aan met api:token.');

            return null;
        }

        return ApiClient::find(select(
            label: 'Bij welke koppeling hoort dit endpoint?',
            options: $clients,
        ));
    }

    /**
     * Determine which events the endpoint subscribes to.
     *
     * @return list<string>
     */
    private function resolveEvents(): array
    {
        $option = $this->option('events');

        if ($option === 'all') {
            return WebhookEvent::ALL;
        }

        if ($option) {
            $requested = array_values(array_filter(array_map('trim', explode(',', $option))));
            $unknown = array_diff($requested, WebhookEvent::ALL);

            if ($unknown !== []) {
                $this->components->error('Onbekende events: '.implode(', ', $unknown));

                return [];
            }

            return $requested;
        }

        /** @var list<string> $selected */
        $selected = multiselect(
            label: 'Welke events stuurt dit endpoint?',
            options: array_combine(
                WebhookEvent::ALL,
                array_map(
                    fn (string $event): string => $event.' — '.WebhookEvent::label($event),
                    WebhookEvent::ALL,
                ),
            ),
            default: WebhookEvent::ALL,
            required: true,
        );

        return $selected;
    }
}
