<?php

declare(strict_types=1);

namespace App\Domains\Common\Media\Services;

use InvalidArgumentException;

final class MediaStorage
{
    public function uploadDisk(): string
    {
        $disk = (string) config('media.disk', 'public');

        if ($disk === '' || config("filesystems.disks.{$disk}") === null) {
            throw new InvalidArgumentException("Unsupported media disk: {$disk}");
        }

        return $disk;
    }

    /**
     * @return array<string, string>
     */
    public function publicWriteOptions(): array
    {
        $options = ['visibility' => 'public'];
        $cacheControl = trim((string) config('media.cache_control', ''));

        if ($cacheControl !== '') {
            $options['CacheControl'] = $cacheControl;
        }

        return $options;
    }
}
