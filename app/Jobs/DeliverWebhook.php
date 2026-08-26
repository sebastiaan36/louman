<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Posts one event to one endpoint, signed so the receiver can verify it came
 * from us. A failed attempt is retried with a growing delay; after the last
 * attempt the delivery is marked failed and can be resent by hand.
 */
class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * How often the delivery is attempted before giving up.
     */
    public int $tries = 8;

    /**
     * How long the receiver gets to answer, in seconds.
     */
    private const RequestTimeout = 15;

    public function __construct(public WebhookDelivery $delivery) {}

    /**
     * Wait longer after each failed attempt: one minute, then five, and so on
     * up to twelve hours — roughly a day of retries in total.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 3600, 7200, 21600, 43200];
    }

    public function handle(): void
    {
        $delivery = $this->delivery->fresh();

        if ($delivery === null || $delivery->delivered_at !== null) {
            return;
        }

        $endpoint = $delivery->endpoint;

        if ($endpoint === null || ! $endpoint->is_active) {
            $delivery->update([
                'failed_at' => now(),
                'error' => 'Endpoint is niet meer actief.',
            ]);

            return;
        }

        $body = json_encode($delivery->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = now()->timestamp;

        $delivery->increment('attempts');
        $delivery->update(['last_attempt_at' => now()]);

        try {
            $response = Http::timeout(self::RequestTimeout)
                ->withBody($body, 'application/json')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'Louman-Webhook/1',
                    'X-Louman-Event' => $delivery->event,
                    'X-Louman-Delivery' => $delivery->uuid,
                    'X-Louman-Timestamp' => (string) $timestamp,
                    'X-Louman-Signature' => 'sha256='.$this->sign($timestamp, $body, $endpoint->secret),
                ])
                ->post($endpoint->url);
        } catch (Throwable $e) {
            $delivery->update(['error' => $e->getMessage()]);

            throw $e;
        }

        $delivery->update([
            'response_status' => $response->status(),
            'response_body' => mb_substr((string) $response->body(), 0, WebhookDelivery::MaxStoredResponseLength),
            'error' => null,
        ]);

        if ($response->successful()) {
            $delivery->update(['delivered_at' => now()]);

            return;
        }

        throw new \RuntimeException(
            "Webhook {$delivery->event} afgewezen door {$endpoint->url} met status {$response->status()}.",
        );
    }

    /**
     * Sign the timestamp and body together, so a captured delivery cannot be
     * replayed later with a fresh timestamp.
     */
    private function sign(int $timestamp, string $body, string $secret): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }

    /**
     * Called after the final attempt failed.
     */
    public function failed(?Throwable $exception): void
    {
        $delivery = $this->delivery->fresh();

        if ($delivery === null || $delivery->delivered_at !== null) {
            return;
        }

        $delivery->update([
            'failed_at' => now(),
            'error' => $exception?->getMessage() ?? 'Onbekende fout.',
        ]);

        Log::error('Webhook definitief mislukt', [
            'delivery' => $delivery->uuid,
            'event' => $delivery->event,
            'endpoint_id' => $delivery->webhook_endpoint_id,
            'attempts' => $delivery->attempts,
        ]);
    }
}
