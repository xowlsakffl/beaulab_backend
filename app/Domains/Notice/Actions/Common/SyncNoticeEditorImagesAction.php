<?php

namespace App\Domains\Notice\Actions\Common;

use App\Domains\Common\Models\Media\Media;
use App\Domains\Notice\Models\Notice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SyncNoticeEditorImagesAction
{
    /**
     * Sync editor images referenced in notice content.
     * - Promote temp images to notice-owned directory.
     * - Create media rows for promoted / existing owned images.
     * - Delete stale media files not referenced anymore.
     */
    public function execute(Notice $notice, string $content): string
    {
        $disk = 'public';
        $paths = $this->extractLocalStoragePaths($content);

        $ownedPrefix = "notice/{$notice->id}/editor-images/";
        $existingMedia = Media::query()
            ->for($notice)
            ->collection('editor_images')
            ->get()
            ->keyBy('path');

        $resolvedOwnedPaths = [];
        $sortOrder = 0;

        foreach ($paths as $path) {
            if (! str_starts_with($path, 'notice/editor-images/temp/') && ! str_starts_with($path, $ownedPrefix)) {
                continue;
            }

            $finalPath = $path;

            if (str_starts_with($path, 'notice/editor-images/temp/')) {
                if (! Storage::disk($disk)->exists($path)) {
                    continue;
                }

                $extension = pathinfo($path, PATHINFO_EXTENSION);
                $filename = Str::uuid()->toString() . ($extension !== '' ? ".{$extension}" : '');
                $finalPath = "{$ownedPrefix}{$filename}";

                Storage::disk($disk)->move($path, $finalPath);
                $content = $this->replaceStoragePathInContent($content, $path, $finalPath);
            }

            if (! str_starts_with($finalPath, $ownedPrefix)) {
                continue;
            }

            if (! Storage::disk($disk)->exists($finalPath)) {
                continue;
            }

            $media = $existingMedia->get($finalPath);
            if (! $media) {
                $media = Media::query()->create([
                    'model_type' => $notice::class,
                    'model_id' => $notice->id,
                    'collection' => 'editor_images',
                    'disk' => $disk,
                    'path' => $finalPath,
                    'mime_type' => Storage::disk($disk)->mimeType($finalPath) ?: null,
                    'size' => Storage::disk($disk)->size($finalPath),
                    'width' => null,
                    'height' => null,
                    'sort_order' => $sortOrder,
                    'is_primary' => false,
                    'metadata' => null,
                ]);
            } else {
                $media->forceFill(['sort_order' => $sortOrder])->save();
            }

            $resolvedOwnedPaths[] = $finalPath;
            $sortOrder++;
        }

        $resolvedOwnedPaths = array_values(array_unique($resolvedOwnedPaths));

        Media::query()
            ->for($notice)
            ->collection('editor_images')
            ->get()
            ->each(function (Media $media) use ($resolvedOwnedPaths): void {
                if (in_array((string) $media->path, $resolvedOwnedPaths, true)) {
                    return;
                }

                Storage::disk((string) $media->disk)->delete((string) $media->path);
                $media->delete();
            });

        return $content;
    }

    /**
     * @return array<int, string>
     */
    private function extractLocalStoragePaths(string $content): array
    {
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches);

        $rawSrcList = $matches[1] ?? [];
        $paths = [];

        foreach ($rawSrcList as $src) {
            if (! is_string($src) || trim($src) === '') {
                continue;
            }

            $path = $this->normalizeStoragePath($src);
            if ($path === null) {
                continue;
            }

            $paths[] = $path;
        }

        return array_values(array_unique($paths));
    }

    private function normalizeStoragePath(string $src): ?string
    {
        $parsedPath = parse_url($src, PHP_URL_PATH);
        $candidate = is_string($parsedPath) ? $parsedPath : $src;
        $candidate = trim($candidate);

        if ($candidate === '') {
            return null;
        }

        if (str_starts_with($candidate, '/storage/')) {
            return ltrim(substr($candidate, 8), '/');
        }

        if (str_starts_with($candidate, 'storage/')) {
            return ltrim(substr($candidate, 7), '/');
        }

        if (str_starts_with($candidate, 'notice/')) {
            return ltrim($candidate, '/');
        }

        return null;
    }

    private function replaceStoragePathInContent(string $content, string $oldPath, string $newPath): string
    {
        $oldRelative = "/storage/{$oldPath}";
        $newRelative = "/storage/{$newPath}";

        $content = str_replace($oldRelative, $newRelative, $content);

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '') {
            $content = str_replace("{$appUrl}{$oldRelative}", "{$appUrl}{$newRelative}", $content);
        }

        return $content;
    }
}
