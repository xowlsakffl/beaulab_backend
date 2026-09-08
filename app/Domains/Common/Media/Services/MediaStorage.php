<?php

declare(strict_types=1);

namespace App\Domains\Common\Media\Services;

use InvalidArgumentException;

final class MediaStorage
{
    public function uploadDisk(?string $ownerType = null, ?string $collection = null): string
    {
        $disk = $ownerType !== null && self::isPrivateCollection($ownerType, (string) $collection)
            ? (string) config('media.private_disk', 'private_media')
            : (string) config('media.disk', 'public');

        if ($disk === '' || config("filesystems.disks.{$disk}") === null) {
            throw new InvalidArgumentException("Unsupported media disk: {$disk}");
        }

        return $disk;
    }

    public static function isPrivateCollection(string $ownerType, string $collection): bool
    {
        $collections = config('media.private_collections', []);

        return in_array($collection, $collections[$ownerType] ?? [], true);
    }

    /** @return array<string, string> */
    public function writeOptions(string $disk): array
    {
        if ($disk === (string) config('media.private_disk', 'private_media')) {
            return ['visibility' => 'private', 'CacheControl' => 'private, no-store'];
        }

        $options = ['visibility' => 'public'];
        $cacheControl = trim((string) config('media.cache_control', ''));

        if ($cacheControl !== '') {
            $options['CacheControl'] = $cacheControl;
        }

        return $options;
    }
}
