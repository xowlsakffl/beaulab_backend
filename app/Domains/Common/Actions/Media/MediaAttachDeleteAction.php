<?php

namespace App\Domains\Common\Actions\Media;

use App\Domains\Common\Models\Media\Media;
use App\Domains\Common\Queries\Media\MediaAttachDeleteQuery;
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

        return $this->storeOne(
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

            $out[] = $this->storeOne(
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
        Media::query()
            ->for($owner)
            ->collection($collection)
            ->get()
            ->each(function (Media $media): void {
                Storage::disk($media->disk)->delete($media->path);
                $media->delete();
            });
    }

    /** @param array<int, string> $collections */
    public function deleteCollectionMediaBulk(Model $owner, array $collections): void
    {
        foreach ($collections as $collection) {
            $this->deleteCollectionMedia($owner, $collection);
        }
    }

    private function storeOne(
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

        $media = $this->query->create([
            'model_type' => $owner::class,
            'model_id' => $owner->getKey(),
            'collection' => $collection,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $w,
            'height' => $h,
            'sort_order' => max(0, $sortOrder),
            'is_primary' => false,
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
            ],
        ]);

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
