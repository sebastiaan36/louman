<?php

namespace App\Http\Middleware\Api;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects tokens that do not belong to an active API client, or that are used
 * from an IP address outside the client's allowlist.
 */
class EnsureApiClientIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->user();

        if (! $client instanceof ApiClient) {
            return response()->json([
                'message' => 'Dit token hoort niet bij een koppeling.',
            ], 403);
        }

        if (! $client->is_active) {
            return response()->json([
                'message' => 'Deze koppeling is gedeactiveerd.',
            ], 403);
        }

        if (! $client->allowsIpAddress($request->ip())) {
            return response()->json([
                'message' => 'Dit IP-adres is niet toegestaan voor deze koppeling.',
            ], 403);
        }

        $client->forceFill(['last_used_at' => now()])->saveQuietly();

        return $next($request);
    }
}
