<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, string $description, ?Model $subject = null, array $metadata = []): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => empty($metadata) ? null : $metadata,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Record a change made through the integration API. There is no user
     * behind it, so the acting client is stored in the metadata instead.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function recordForApiClient(ApiClient $client, string $action, string $description, ?Model $subject = null, array $metadata = []): void
    {
        static::create([
            'user_id' => null,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => array_merge($metadata, [
                'api_client_id' => $client->id,
                'api_client' => $client->name,
            ]),
            'ip_address' => request()->ip(),
        ]);
    }
}
