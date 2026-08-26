<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

/**
 * An external system that is allowed to talk to the integration API.
 * Tokens are issued per client and carry their own abilities (scopes).
 */
class ApiClient extends Model
{
    /** @use HasFactory<\Database\Factories\ApiClientFactory> */
    use HasApiTokens, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'contact_email',
        'is_active',
        'allowed_ips',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allowed_ips' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include active clients.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Determine if the given IP address may use this client's tokens.
     * An empty allowlist means every address is accepted.
     */
    public function allowsIpAddress(?string $ipAddress): bool
    {
        $allowed = $this->allowed_ips ?? [];

        if ($allowed === []) {
            return true;
        }

        return $ipAddress !== null && in_array($ipAddress, $allowed, true);
    }
}
