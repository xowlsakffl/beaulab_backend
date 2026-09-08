<?php

declare(strict_types=1);

namespace App\Domains\Common\Media\Actions;

use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\Media\Services\MediaFileLifecycle;
use App\Domains\Common\Media\Services\MediaStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class PrivatizeMediaAction
{
    public function __construct(private readonly MediaFileLifecycle $files, private readonly MediaStorage $storage) {}

    public function execute(int $mediaId): bool
    {
        return DB::transaction(function () use ($mediaId): bool {
            $media = Media::query()->lockForUpdate()->find($mediaId);
            $targetDisk = (string) config('media.private_disk', 'private_media');
            if (! $media || ! $media->isPrivate() || $media->disk === $targetDisk) {
                return false;
            }

            $sourceDisk = (string) $media->disk;
            $paths = [$media->path, ...$media->variantPaths()];
            $this->files->stage($targetDisk, $media->path, $media->variantPaths());
            foreach ($paths as $path) {
                $stream = Storage::disk($sourceDisk)->readStream($path);
                if (! is_resource($stream)) {
                    throw new RuntimeException('Source media is missing: '.$mediaId);
                }
                try {
                    $copied = Storage::disk($targetDisk)->writeStream($path, $stream, $this->storage->writeOptions($targetDisk));
                } finally {
                    fclose($stream);
                }
                if (! $copied || $this->checksum($sourceDisk, $path) !== $this->checksum($targetDisk, $path)) {
                    throw new RuntimeException('Private media copy verification failed: '.$mediaId);
                }
            }

            $media->forceFill(['disk' => $targetDisk])->save();
            $this->files->queueDeletion($sourceDisk, $paths);

            return true;
        });
    }

    private function checksum(string $disk, string $path): string
    {
        $stream = Storage::disk($disk)->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException('Cannot read media for verification.');
        }
        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
