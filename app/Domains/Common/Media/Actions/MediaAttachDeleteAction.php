<?php

namespace App\Domains\Common\Media\Actions;

use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\Media\Queries\MediaAttachDeleteQuery;
use App\Domains\Common\Media\Services\MediaVariantGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * MediaAttachDeleteAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class MediaAttachDeleteAction
{
    public function __construct(
        private readonly MediaAttachDeleteQuery $query,
        private readonly MediaVariantGenerator $variantGenerator,
    ) {}

    public function attachOne(
        Model $owner,
        ?UploadedFile $file,
        string $collection,
        string $dirPrefix,
        string $dirName,
        bool $isPrimary = false,
        int $sortOrder = 0,
    ): ?Media {
        if (! $file) {
            return null;
        }

        return $this->createOne(
            $owner,
            $file,
            $collection,
            "{$dirPrefix}/{$owner->getKey()}/{$dirName}",
            $isPrimary,
            $sortOrder,
        );
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, Media>
     */
    public function attachMany(
        Model $owner,
        array $files,
        string $collection,
        string $dirPrefix,
        string $dirName,
        bool $firstPrimary = false,
    ): array {
        $out = [];

        foreach (array_values($files) as $i => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $out[] = $this->createOne(
                $owner,
                $file,
                $collection,
                "{$dirPrefix}/{$owner->getKey()}/{$dirName}",
                $firstPrimary && $i === 0,
                $i,
            );
        }

        return $out;
    }

    public function deleteCollectionMedia(Model $owner, string $collection): void
    {
        $this->query
            ->collectionMedia($owner, $collection)
            ->each(function (Media $media): void {
                $this->delete($media);
            });
    }

    /** @param array<int, string> $collections */
    public function deleteCollectionMediaBulk(Model $owner, array $collections): void
    {
        foreach ($collections as $collection) {
            $this->deleteCollectionMedia($owner, $collection);
        }
    }

    public function delete(Media $media): void
    {
        Storage::disk($media->disk)->delete([
            $media->path,
            ...$media->variantPaths(),
        ]);
        $this->query->delete($media);
    }

    private function createOne(
        Model $owner,
        UploadedFile $file,
        string $collection,
        string $dir,
        bool $isPrimary,
        int $sortOrder,
    ): Media {
        $disk = 'public';
        $path = Storage::disk($disk)->putFile($dir, $file);

        [$w, $h] = $this->imageSize($file);
        $mimeType = $file->getMimeType();
        $variants = $this->variantGenerator->generate($disk, $path, $mimeType);

        try {
            $media = $this->query->create([
                'model_type' => $owner::class,
                'model_id' => $owner->getKey(),
                'collection' => $collection,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => $file->getSize(),
                'width' => $w,
                'height' => $h,
                'sort_order' => max(0, $sortOrder),
                'is_primary' => false,
                'metadata' => [
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => $file->getClientOriginalExtension(),
                    'variants' => $variants,
                ],
            ]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete([
                $path,
                ...collect($variants)
                    ->pluck('path')
                    ->filter()
                    ->values()
                    ->all(),
            ]);

            throw $exception;
        }

        if ($isPrimary) {
            $media->setPrimary(true);
        }

        return $media;
    }

    /**
     * @return array{0:int|null,1:int|null}
     */
    private function imageSize(UploadedFile $file): array
    {
        try {
            $info = @getimagesize($file->getRealPath());

            if (! $info) {
                return [null, null];
            }

            return [(int) $info[0], (int) $info[1]];
        } catch (\Throwable) {
            return [null, null];
        }
    }
}
