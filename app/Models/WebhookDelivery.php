<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One event queued for one endpoint, with the outcome of every attempt. This
 * is the log to look at when the external package says it missed something.
 */
class WebhookDelivery extends Model
{
    /**
     * Responses longer than this are truncated before being stored.
     */
    public const MaxStoredResponseLength = 2000;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'webhook_endpoint_id',
        'uuid',
        'event',
        'payload',
        'attempts',
        'response_status',
        'response_body',
        'error',
        'last_attempt_at',
        'delivered_at',
        'failed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'last_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    /**
     * Scope a query to deliveries that gave up after all retries.
     */
    public function scopeFailed(Builder $query): void
    {
        $query->whereNotNull('failed_at');
    }

    /**
     * Scope a query to deliveries that are still on their way.
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('delivered_at')->whereNull('failed_at');
    }
}
