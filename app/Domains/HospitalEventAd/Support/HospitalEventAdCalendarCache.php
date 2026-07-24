<?php

namespace App\Domains\HospitalEventAd\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

final class HospitalEventAdCalendarCache
{
    private const int DEFAULT_TTL_SECONDS = 60;

    private const string VERSION_KEY = 'hospital-event-ad:calendar:version';

    /**
     * @return array<string, mixed>
     */
    public static function rememberCalendar(string $group, ?int $categoryId, CarbonInterface $month, callable $resolver): array
    {
        return self::remember(
            'calendar',
            [
                $group,
                $categoryId !== null ? (string) $categoryId : 'all',
                $month->format('Y-m'),
                today()->toDateString(),
            ],
            $resolver,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function rememberAvailability(string $placement, ?int $categoryId, CarbonInterface $month, callable $resolver): array
    {
        return self::remember(
            'availability',
            [
                $placement,
                $categoryId !== null ? (string) $categoryId : 'none',
                $month->format('Y-m'),
                today()->toDateString(),
            ],
            $resolver,
        );
    }

    public static function flush(): void
    {
        try {
            Cache::store(self::storeName())->forever(self::VERSION_KEY, self::newVersion());
        } catch (Throwable) {
            // Calendar cache is display-only. Invalidation failure must not block writes.
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function remember(string $type, array $parts, callable $resolver): array
    {
        $key = self::key($type, $parts);

        try {
            $cached = Cache::store(self::storeName())->get($key);

            if (is_array($cached)) {
                return $cached;
            }
        } catch (Throwable) {
            return $resolver();
        }

        /** @var array<string, mixed> $value */
        $value = $resolver();

        try {
            Cache::store(self::storeName())->put($key, $value, self::ttlSeconds());
        } catch (Throwable) {
            // Calendar cache is display-only. Cache write failure must not block reads.
        }

        return $value;
    }

    private static function key(string $type, array $parts): string
    {
        return sprintf(
            'hospital-event-ad:%s:%s:%s',
            $type,
            self::version(),
            sha1(json_encode(array_values($parts), JSON_THROW_ON_ERROR)),
        );
    }

    private static function version(): string
    {
        try {
            $version = Cache::store(self::storeName())->get(self::VERSION_KEY);

            if (is_string($version) && $version !== '') {
                return $version;
            }

            $version = self::newVersion();
            Cache::store(self::storeName())->forever(self::VERSION_KEY, $version);

            return $version;
        } catch (Throwable) {
            return 'fallback';
        }
    }

    private static function newVersion(): string
    {
        return (string) Str::uuid();
    }

    private static function storeName(): string
    {
        $store = config('cache.hospital_event_ad_calendar.store', 'redis');

        return is_string($store) && $store !== '' ? $store : 'redis';
    }

    private static function ttlSeconds(): int
    {
        $ttl = (int) config('cache.hospital_event_ad_calendar.ttl_seconds', self::DEFAULT_TTL_SECONDS);

        return $ttl > 0 ? $ttl : self::DEFAULT_TTL_SECONDS;
    }
}
