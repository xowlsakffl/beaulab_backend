<?php

declare(strict_types=1);

namespace App\Domains\Common\Cache\Support;

use Illuminate\Support\Facades\Cache;
use Throwable;

final class StaffSummaryCache
{
    public const DOMAIN_ACCOUNT_USER = 'account-user';

    public const DOMAIN_CONTENT_REPORT = 'content-report';

    public const DOMAIN_HOSPITAL = 'hospital';

    public const DOMAIN_HOSPITAL_ENTRY = 'hospital-entry';

    public const DOMAIN_HOSPITAL_EVENT = 'hospital-event';

    public const DOMAIN_HOSPITAL_VIDEO = 'hospital-video';

    private const int DEFAULT_TTL_SECONDS = 300;

    /**
     * @return array<string, mixed>
     */
    public static function remember(string $domain, callable $resolver, array $parts = []): array
    {
        $key = self::key($domain, $parts);

        try {
            $cached = Cache::store(self::storeName())->get($key);

            if (is_array($cached)) {
                return $cached;
            }
        } catch (Throwable) {
            return $resolver();
        }

        /** @var array<string, mixed> $summary */
        $summary = $resolver();

        try {
            Cache::store(self::storeName())->put($key, $summary, self::ttlSeconds());
        } catch (Throwable) {
            // Summary cache is an acceleration layer. Redis failures must not block reads.
        }

        return $summary;
    }

    public static function forget(string $domain, array $parts = []): void
    {
        try {
            Cache::store(self::storeName())->forget(self::key($domain, $parts));
        } catch (Throwable) {
            // Summary cache is an acceleration layer. Redis failures must not block writes.
        }
    }

    private static function key(string $domain, array $parts): string
    {
        $suffix = $parts === []
            ? 'default'
            : sha1(json_encode(array_values($parts), JSON_THROW_ON_ERROR));

        return "staff:summary:{$domain}:{$suffix}";
    }

    private static function storeName(): string
    {
        $store = config('cache.staff_summary.store', 'redis');

        return is_string($store) && $store !== '' ? $store : 'redis';
    }

    private static function ttlSeconds(): int
    {
        $ttl = (int) config('cache.staff_summary.ttl_seconds', self::DEFAULT_TTL_SECONDS);

        return $ttl > 0 ? $ttl : self::DEFAULT_TTL_SECONDS;
    }
}
