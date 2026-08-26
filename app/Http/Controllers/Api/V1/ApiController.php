<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class ApiController extends Controller
{
    protected const DefaultPerPage = 100;

    protected const MaxPerPage = 500;

    /**
     * Get the API client behind the current request.
     */
    protected function client(Request $request): ApiClient
    {
        /** @var \App\Models\ApiClient $client */
        $client = $request->user();

        return $client;
    }

    /**
     * Limit a query to records changed since the given moment, so the caller
     * can poll for deltas instead of pulling everything each run.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function applyUpdatedSince(Builder $query, Request $request, string $column = 'updated_at'): void
    {
        $value = $request->query('updated_since');

        if (blank($value)) {
            return;
        }

        try {
            $since = now()->parse($value);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'updated_since' => 'Gebruik een geldige datum/tijd, bijvoorbeeld 2026-08-01T00:00:00Z.',
            ]);
        }

        $query->where($column, '>=', $since);
    }

    /**
     * Resolve the page size, capped so one call can never pull the whole table.
     */
    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', (string) static::DefaultPerPage);

        return max(1, min($perPage, static::MaxPerPage));
    }
}
