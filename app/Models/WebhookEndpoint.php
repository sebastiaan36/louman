<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A URL at the external package that the portal pushes events to. The secret
 * is used to sign every delivery so the receiver can prove it came from us.
 */
class WebhookEndpoint extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookEndpointFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'api_client_id',
        'url',
        'secret',
        'events',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'secret' => 'encrypted',
            'disabled_at' => 'datetime',
        ];
    }

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Scope a query to endpoints that should currently receive events.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereHas('apiClient', fn (Builder $client) => $client->where('is_active', true));
    }

    /**
     * Scope a query to endpoints subscribed to the given event.
     */
    public function scopeSubscribedTo(Builder $query, string $event): void
    {
        $query->whereJsonContains('events', $event);
    }
}
