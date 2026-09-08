<?php

namespace App\Domains\Faq\Actions\Common;

use App\Domains\Common\Media\Models\Media;
use App\Domains\Faq\Models\Faq;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * SyncFaqEditorImagesAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class SyncFaqEditorImagesAction
{
    public function execute(Faq $faq, string $content): string
    {
        $disk = 'public';
        $paths = $this->extractLocalStoragePaths($content);

        $ownedPrefix = "faq/{$faq->id}/editor-images/";
        $existingMedia = Media::query()
            ->for($faq)
            ->collection('editor_images')
            ->get()
            ->keyBy('path');

        $resolvedOwnedPaths = [];
        $sortOrder = 0;

        foreach ($paths as $path) {
            if (! str_starts_with($path, \App\Domains\Common\Media\Support\EditorImagePath::temporaryPrefix('faq')) && ! str_starts_with($path, $ownedPrefix)) {
                continue;
            }

            $finalPath = $path;

            if (str_starts_with($path, \App\Domains\Common\Media\Support\EditorImagePath::temporaryPrefix('faq'))) {
                if (! Storage::disk($disk)->exists($path)) {
                    continue;
                }

                $extension = pathinfo($path, PATHINFO_EXTENSION);
                $filename = Str::uuid()->toString().($extension !== '' ? ".{$extension}" : '');
                $finalPath = "{$ownedPrefix}{$filename}";

                $files = app(\App\Domains\Common\Media\Services\MediaFileLifecycle::class);
                $files->stage($disk, $finalPath);
                if (! Storage::disk($disk)->copy($path, $finalPath)) {
                    throw new \RuntimeException('Editor image promotion failed.');
                }
                $files->queueDeletion($disk, [$path]);
                $content = $this->replaceStoragePathInContent($content, $path, $finalPath);
            }

            if (! str_starts_with($finalPath, $ownedPrefix) || ! Storage::disk($disk)->exists($finalPath)) {
                continue;
            }

            $media = $existingMedia->get($finalPath);
            if (! $media) {
                Media::query()->create([
                    'model_type' => $faq::class,
                    'model_id' => $faq->id,
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
            ->for($faq)
            ->collection('editor_images')
            ->get()
            ->each(function (Media $media) use ($resolvedOwnedPaths): void {
                if (in_array((string) $media->path, $resolvedOwnedPaths, true)) {
                    return;
                }

                app(\App\Domains\Common\Media\Actions\MediaAttachDeleteAction::class)->delete($media);
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
        return \App\Domains\Common\Media\Support\EditorImagePath::normalize($src);
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
