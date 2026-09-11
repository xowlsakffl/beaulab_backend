<?php

namespace App\Domains\HospitalPromotion\Actions\Common;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\Media\Services\MediaFileLifecycle;
use App\Domains\Common\Media\Support\EditorHtmlSanitizer;
use App\Domains\Common\Media\Support\EditorImagePath;
use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use DOMDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class HospitalPromotionEditorImagesAction
{
    public const TEMP_COLLECTION = 'hospital_promotion_editor_temp';

    public function __construct(
        private readonly MediaAttachDeleteAction $mediaAction,
        private readonly MediaFileLifecycle $files,
    ) {}

    public function upload(UploadedFile $image, ?int $promotionId): array
    {
        if ($promotionId === null) {
            Gate::authorize('create', HospitalPromotion::class);
        } else {
            Gate::authorize('update', HospitalPromotion::query()->findOrFail($promotionId));
        }

        return DB::transaction(function () use ($image, $promotionId): array {
            // Edits also upload temporary media; only save may change permanent attachments.
            $media = $this->mediaAction->attachOne(auth()->user(), $image, self::TEMP_COLLECTION, 'hospital-promotion/editor-images/temp', 'uploads');
            $media->forceFill(['metadata' => array_merge($media->metadata ?? [], ['promotion_id' => $promotionId])])->save();

            return ['url' => $media->publicUrl(), 'path' => (string) $media->path];
        });
    }

    public function cleanup(array $paths = [], array $urls = []): array
    {
        Gate::authorize('manageEditorImages', HospitalPromotion::class);
        $targets = [];
        foreach ([...$paths, ...$urls] as $value) {
            $path = EditorImagePath::temporary('hospital-promotion', $value);
            if ($path !== null) {
                $targets[] = $path;
            }
        }
        $targets = array_values(array_unique($targets));

        return DB::transaction(function () use ($targets): array {
            $media = $this->ownedTemporary()->whereIn('path', $targets)->orderBy('id')->lockForUpdate()->get();
            foreach ($media as $item) {
                $this->mediaAction->delete($item);
            }

            return ['requested_count' => count($targets), 'deleted_count' => $media->count(), 'deleted_paths' => $media->pluck('path')->all()];
        });
    }

    /** Caller holds the slot and promotion locks in a transaction. */
    public function sync(HospitalPromotion $promotion, string $content): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><html><body>'.EditorHtmlSanitizer::clean($content).'</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $images = iterator_to_array($document->getElementsByTagName('img'));
        if ($images === [] && preg_match('/[^\s\p{Z}\x{200B}\x{FEFF}]/u', $document->getElementsByTagName('body')->item(0)->textContent) !== 1) {
            throw ValidationException::withMessages(['content' => '내용을 입력해 주세요.']);
        }
        $paths = [];
        foreach ($images as $image) {
            $path = EditorImagePath::normalize($image->getAttribute('src'));
            if ($path === null) {
                $this->invalidImage();
            }
            $paths[] = $path;
        }

        $temporary = $this->ownedTemporary()->whereIn('path', array_unique($paths))->orderBy('id')->lockForUpdate()->get()->keyBy('path');
        $existing = $promotion->editorImages()->get()->keyBy('path');
        $resolved = [];
        foreach (array_unique($paths) as $path) {
            $media = $existing->get($path);
            if ($media === null) {
                $temp = $temporary->get($path);
                if ($temp === null || ! str_starts_with($path, EditorImagePath::temporaryPrefix('hospital-promotion'))
                    || $temp->created_at->lte(now()->subDay())
                    || (($temp->metadata['promotion_id'] ?? null) !== null && (int) $temp->metadata['promotion_id'] !== (int) $promotion->id)) {
                    $this->invalidImage();
                }
                $disk = (string) $temp->disk;
                if (! Storage::disk($disk)->exists($path)) {
                    $this->invalidImage();
                }
                $finalPath = 'hospital-promotion/'.$promotion->id.'/editor-images/'.Str::uuid().'.'.pathinfo($path, PATHINFO_EXTENSION);
                $this->files->stage($disk, $finalPath);
                if (! Storage::disk($disk)->copy($path, $finalPath)) {
                    throw new \RuntimeException('Hospital promotion editor image promotion failed.');
                }
                $media = Media::query()->create([
                    'model_type' => HospitalPromotion::class, 'model_id' => $promotion->id,
                    'collection' => 'editor_images', 'disk' => $disk, 'path' => $finalPath,
                    'mime_type' => $temp->mime_type, 'size' => $temp->size,
                    'width' => $temp->width, 'height' => $temp->height,
                    'sort_order' => count($resolved), 'is_primary' => false,
                    'metadata' => ['original_name' => basename($finalPath), 'variants' => []],
                ]);
                $this->mediaAction->delete($temp);
            } else {
                if (! Storage::disk((string) $media->disk)->exists((string) $media->path)) {
                    $this->invalidImage();
                }
                $media->setSortOrder(count($resolved));
            }
            $resolved[$path] = $media;
        }
        foreach ($images as $index => $image) {
            $image->setAttribute('src', $resolved[$paths[$index]]->publicUrl());
        }
        $keptIds = array_map(fn (Media $media) => (int) $media->id, $resolved);
        foreach ($existing as $media) {
            if (! in_array((int) $media->id, $keptIds, true)) {
                $this->mediaAction->delete($media);
            }
        }

        $html = '';
        foreach ($document->getElementsByTagName('body')->item(0)->childNodes as $node) {
            $html .= $document->saveHTML($node);
        }

        return EditorHtmlSanitizer::clean($html);
    }

    public function pruneExpired(int $limit = 500): int
    {
        $cutoff = now()->subDay();
        $ids = Media::query()->where('model_type', AccountStaff::class)->collection(self::TEMP_COLLECTION)
            ->where('created_at', '<=', $cutoff)->orderBy('id')->limit($limit)->pluck('id');
        $count = 0;
        foreach ($ids as $id) {
            $count += DB::transaction(function () use ($id, $cutoff): int {
                $media = Media::query()->where('model_type', AccountStaff::class)->collection(self::TEMP_COLLECTION)
                    ->where('created_at', '<=', $cutoff)->lockForUpdate()->find($id);
                if ($media === null) {
                    return 0;
                }
                $this->mediaAction->delete($media);

                return 1;
            });
        }

        return $count;
    }

    private function ownedTemporary(): Builder
    {
        return Media::query()->where('model_type', AccountStaff::class)->where('model_id', auth()->id())->collection(self::TEMP_COLLECTION);
    }

    private function invalidImage(): never
    {
        throw ValidationException::withMessages(['content' => '본문 이미지는 현재 프로모션 이미지 또는 본인이 업로드한 유효한 프로모션 임시 이미지만 사용할 수 있습니다. 다시 업로드해 주세요.']);
    }
}
