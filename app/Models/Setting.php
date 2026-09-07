<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const MAIL_ORDER_NOTIFICATION = 'mail_order_notification';

    public const MAIL_REGISTRATION_NOTIFICATION = 'mail_registration_notification';

    public const MAIL_REGISTRATION_CC = 'mail_registration_cc';

    public const MAIL_CANCELLATION_NOTIFICATION = 'mail_cancellation_notification';

    public const MAIL_SUPPORT_NOTIFICATION = 'mail_support_notification';

    public const SUPPORT_PHONE = 'support_phone';

    public const MAIL_CC = 'mail_cc';

    public const MAIL_REPLY_TO = 'mail_reply_to';

    /**
     * Cache key holding all settings as a key => value map.
     */
    private const CACHE_KEY = 'settings.all';

    /**
     * Get a setting value by key, falling back to the given default.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::cached()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * Persist a setting value and flush the cache.
     */
    public static function set(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * All settings as a key => value map, cached across the request.
     *
     * @return array<string, string|null>
     */
    private static function cached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => self::query()->pluck('value', 'key')->all());
    }
}
