<?php

namespace App\Http\Middleware\Api;

use App\Models\ApiIdempotencyKey;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires an Idempotency-Key on every write, and replays the stored response
 * when the same key comes back. A retry after a timeout is therefore safe:
 * it never writes twice.
 */
class EnforceIdempotency
{
    /**
     * Responses larger than this are not stored; a retry re-runs instead.
     */
    private const MaxStoredResponseBytes = 64 * 1024;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (blank($key)) {
            return response()->json([
                'message' => 'Stuur een Idempotency-Key header mee bij schrijfacties.',
            ], 400);
        }

        if (strlen($key) > 128) {
            return response()->json([
                'message' => 'De Idempotency-Key mag maximaal 128 tekens lang zijn.',
            ], 400);
        }

        /** @var \App\Models\ApiClient $client */
        $client = $request->user();
        $hash = $this->fingerprint($request);

        $existing = ApiIdempotencyKey::query()
            ->where('api_client_id', $client->id)
            ->where('key', $key)
            ->first();

        if ($existing !== null) {
            return $this->handleReplay($existing, $hash);
        }

        try {
            $record = ApiIdempotencyKey::create([
                'api_client_id' => $client->id,
                'key' => $key,
                'endpoint' => $request->method().' '.$request->path(),
                'request_hash' => $hash,
            ]);
        } catch (QueryException) {
            return response()->json([
                'message' => 'Een verzoek met deze Idempotency-Key wordt al verwerkt.',
            ], 409);
        }

        $response = $next($request);

        $this->storeResponse($record, $response);

        return $response;
    }

    /**
     * Decide what to return when a key that was seen before comes back.
     */
    private function handleReplay(ApiIdempotencyKey $existing, string $hash): Response
    {
        if ($existing->request_hash !== $hash) {
            return response()->json([
                'message' => 'Deze Idempotency-Key is al gebruikt voor een ander verzoek.',
            ], 409);
        }

        if (! $existing->hasStoredResponse()) {
            return response()->json([
                'message' => 'Een verzoek met deze Idempotency-Key wordt al verwerkt.',
            ], 409);
        }

        return response(
            $existing->response_body ?? '',
            $existing->response_status,
        )->header('Content-Type', 'application/json')
            ->header('Idempotent-Replay', 'true');
    }

    /**
     * Build a fingerprint of the request so a reused key with different content
     * can be spotted.
     */
    private function fingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->method(),
            $request->path(),
            $request->getContent(),
        ]));
    }

    /**
     * Persist a successful response so a retry can replay it. Failed requests
     * keep no response, letting the caller correct and resend the same key.
     */
    private function storeResponse(ApiIdempotencyKey $record, Response $response): void
    {
        if ($response->getStatusCode() >= 400) {
            $record->delete();

            return;
        }

        $body = $response->getContent();

        if ($body === false || strlen($body) > self::MaxStoredResponseBytes) {
            $record->delete();

            return;
        }

        $record->update([
            'response_status' => $response->getStatusCode(),
            'response_body' => $body,
        ]);
    }
}
