<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Keep existing cache keys/TTL and reject fills started before a review commit. */
class ReviewRatingCache
{
    private const VERSION_KEY = 'reviews.public-cache-version';

    public static function remember(string $key, int $seconds, Closure $callback): mixed
    {
        $version = Cache::get(self::VERSION_KEY);
        if (! is_string($version)) {
            Cache::add(self::VERSION_KEY, (string) Str::uuid(), now()->addYears(10));
            $version = Cache::get(self::VERSION_KEY);
        }
        $entry = Cache::get($key);
        if (is_string($version) && is_array($entry)
            && ($entry['review_version'] ?? null) === $version && array_key_exists('value', $entry)) {
            return $entry['value'];
        }

        $value = $callback();
        // Never tag a late fill with the newer version. The next reader will reject it.
        Cache::put($key, ['review_version' => $version, 'value' => $value], $seconds);
        return $value;
    }

    public static function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, (string) Str::uuid());
    }
}
