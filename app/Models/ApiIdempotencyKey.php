<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record of a write request the API has already accepted, so that a retry
 * with the same Idempotency-Key replays the original response instead of
 * writing a second time.
 */
class ApiIdempotencyKey extends Model
{
    protected $fillable = [
        'api_client_id',
        'key',
        'endpoint',
        'request_hash',
        'response_status',
        'response_body',
    ];

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * Determine if the original request has finished and its response was stored.
     */
    public function hasStoredResponse(): bool
    {
        return $this->response_status !== null;
    }
}
