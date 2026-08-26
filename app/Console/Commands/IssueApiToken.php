<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use App\Support\ApiAbility;
use Illuminate\Console\Command;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;

/**
 * Issues an access token for an integration. The token is shown once and is
 * not recoverable afterwards; a lost token is replaced, never looked up.
 */
class IssueApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:token
        {--client= : Naam van een bestaande koppeling, of de naam voor een nieuwe}
        {--abilities= : Komma-gescheiden rechten, bijvoorbeeld products:write,orders:read}
        {--expires= : Aantal dagen dat het token geldig blijft}
        {--ips= : Komma-gescheiden lijst van toegestane IP-adressen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Maak een API-token aan voor een koppeling met een extern pakket';

    public function handle(): int
    {
        $name = $this->option('client') ?: text(
            label: 'Voor welke koppeling is dit token?',
            placeholder: 'Bijvoorbeeld: Boekhoudpakket productie',
            required: true,
        );

        $client = ApiClient::firstOrCreate(['name' => $name]);

        if ($ips = $this->option('ips')) {
            $client->update(['allowed_ips' => $this->splitList($ips)]);
        }

        $abilities = $this->resolveAbilities();

        if ($abilities === []) {
            $this->components->error('Kies minimaal één recht.');

            return self::FAILURE;
        }

        $expiresAt = $this->option('expires')
            ? now()->addDays((int) $this->option('expires'))
            : null;

        $token = $client->createToken($name, $abilities, $expiresAt);

        $this->newLine();
        $this->components->info("Token aangemaakt voor koppeling '{$client->name}'.");
        $this->components->twoColumnDetail('Rechten', implode(', ', $abilities));
        $this->components->twoColumnDetail(
            'Geldig tot',
            $expiresAt?->format('d-m-Y H:i') ?? 'geen vervaldatum',
        );
        $this->components->twoColumnDetail(
            'Toegestane IP-adressen',
            $client->allowed_ips ? implode(', ', $client->allowed_ips) : 'alle',
        );
        $this->newLine();
        $this->components->warn('Bewaar dit token nu — het wordt hierna niet meer getoond:');
        $this->line('  '.$token->plainTextToken);
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Determine which abilities the token should carry.
     *
     * @return list<string>
     */
    private function resolveAbilities(): array
    {
        if ($option = $this->option('abilities')) {
            $requested = $this->splitList($option);
            $unknown = array_diff($requested, ApiAbility::ALL);

            if ($unknown !== []) {
                $this->components->error('Onbekende rechten: '.implode(', ', $unknown));

                return [];
            }

            return $requested;
        }

        /** @var list<string> $selected */
        $selected = multiselect(
            label: 'Welke rechten krijgt dit token?',
            options: array_combine(
                ApiAbility::ALL,
                array_map(
                    fn (string $ability): string => $ability.' — '.ApiAbility::label($ability),
                    ApiAbility::ALL,
                ),
            ),
            required: true,
        );

        return $selected;
    }

    /**
     * Split a comma-separated option into a clean list.
     *
     * @return list<string>
     */
    private function splitList(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
