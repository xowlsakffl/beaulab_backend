<?php

declare(strict_types=1);

namespace App\Domains\Common\Media\Services;

use App\Domains\Common\Media\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class MediaFileLifecycle
{
    public function stage(string $disk, string $path, array $extraPaths = []): void
    {
        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $paths = [$path, ...$extraPaths];
        foreach (['thumb', 'medium'] as $variant) {
            $paths[] = $directory.'/'.$name.'-'.$variant.($extension !== '' ? '.'.$extension : '');
        }

        // This filesystem manifest survives a DB rollback or a killed PHP process.
        $stored = Storage::disk((string) config('media.private_disk', 'private_media'))->put('staging/'.Str::uuid().'.json', json_encode([
            'disk' => $disk, 'path' => $path, 'paths' => $paths,
        ], JSON_THROW_ON_ERROR), ['visibility' => 'private']);
        if (! $stored) {
            throw new RuntimeException('Media staging manifest could not be saved.');
        }
    }

    public function queueDeletion(string $disk, array $paths): void
    {
        DB::table('media_file_deletions')->insert([
            'disk' => $disk,
            'paths' => json_encode(array_values(array_unique($paths)), JSON_THROW_ON_ERROR),
            'available_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function purge(int $limit = 100): int
    {
        $count = 0;
        $ids = DB::table('media_file_deletions')->where('available_at', '<=', now())->orderBy('id')->limit($limit)->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$count): void {
                $task = DB::table('media_file_deletions')->where('id', $id)->lockForUpdate()->first();
                if (! $task || $task->available_at > now()->toDateTimeString()) {
                    return;
                }

                try {
                    $paths = json_decode($task->paths, true, flags: JSON_THROW_ON_ERROR);
                    if (Media::query()->where('disk', $task->disk)->whereIn('path', $paths)->exists()) {
                        DB::table('media_file_deletions')->where('id', $id)->delete();

                        return;
                    }
                    foreach ($paths as $path) {
                        if (! Storage::disk($task->disk)->delete($path)) {
                            throw new RuntimeException('Media file deletion failed.');
                        }
                    }
                    DB::table('media_file_deletions')->where('id', $id)->delete();
                    $count++;
                } catch (Throwable $exception) {
                    DB::table('media_file_deletions')->where('id', $id)->update([
                        'attempts' => (int) $task->attempts + 1,
                        'available_at' => now()->addMinutes(5),
                        'last_error' => mb_substr($exception->getMessage(), 0, 1000),
                        'updated_at' => now(),
                    ]);
                    report($exception);
                }
            });
        }

        return $count;
    }

    public function pruneStaging(int $limit = 500): int
    {
        $disk = Storage::disk((string) config('media.private_disk', 'private_media'));
        $count = 0;
        foreach ($disk->getDriver()->listContents('staging', false) as $file) {
            if ($count >= $limit) {
                break;
            }
            if (! $file->isFile()) {
                continue;
            }
            $manifest = $file->path();
            if ($disk->lastModified($manifest) > now()->subDay()->timestamp) {
                continue;
            }
            $data = json_decode($disk->get($manifest), true, flags: JSON_THROW_ON_ERROR);
            if (! Media::withTrashed()->where('disk', $data['disk'])->where('path', $data['path'])->exists()) {
                if (! Storage::disk($data['disk'])->delete($data['paths'])) {
                    throw new RuntimeException('Orphan media cleanup failed.');
                }
            }
            $disk->delete($manifest);
            $count++;
        }

        return $count;
    }
}
