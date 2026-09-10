<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Http\UploadedFile;

final class HospitalEventBeforeAfterPhotosSyncAction
{
    public function __construct(private readonly MediaAttachDeleteAction $mediaAction) {}

    public function execute(HospitalEvent $event, array $payload): void
    {
        if ($event->event_type !== HospitalEvent::TYPE_TEXT) {
            if (! empty($payload['before_after_photos'])) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '전후사진은 텍스트 등록에서만 사용할 수 있습니다.');
            }
            $this->mediaAction->deleteCollectionMediaBulk($event, [HospitalEvent::COLLECTION_BEFORE_PHOTO, HospitalEvent::COLLECTION_AFTER_PHOTO]);

            return;
        }

        if (! array_key_exists('before_after_photos', $payload)) {
            return;
        }

        $pairs = $this->resolve($event, $payload['before_after_photos']);
        $existing = $event->beforeAfterPhotos()->get();
        $retainedIds = [];
        foreach ($pairs as $index => $pair) {
            foreach ($pair as $side => $photo) {
                if ($photo instanceof Media) {
                    $photo->update(['sort_order' => $index]);
                    $retainedIds[] = $photo->id;
                } else {
                    $collection = $side === 'before' ? HospitalEvent::COLLECTION_BEFORE_PHOTO : HospitalEvent::COLLECTION_AFTER_PHOTO;
                    $this->mediaAction->attachOne($event, $photo, $collection, 'hospital-event', 'before-after-photos/'.$side, false, $index);
                }
            }
        }
        foreach ($existing as $media) {
            if (! in_array($media->id, $retainedIds, true)) {
                $this->mediaAction->delete($media);
            }
        }
        $event->unsetRelation('beforeAfterPhotos');
    }

    /** @return array<int, array{before:Media|UploadedFile,after:Media|UploadedFile}> */
    public function resolve(HospitalEvent $owner, array $pairs): array
    {
        if (count($pairs) > HospitalEvent::MAX_BEFORE_AFTER_PHOTOS) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '전후사진은 최대 4세트까지 등록할 수 있습니다.');
        }
        $mediaById = $owner->beforeAfterPhotos()->get()->keyBy('id');
        $usedIds = [];
        $resolved = [];
        foreach (array_values($pairs) as $index => $pair) {
            if (! is_array($pair)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '전후사진 형식이 올바르지 않습니다.');
            }
            $row = [];
            foreach (['before' => HospitalEvent::COLLECTION_BEFORE_PHOTO, 'after' => HospitalEvent::COLLECTION_AFTER_PHOTO] as $side => $collection) {
                $file = $pair[$side.'_image'] ?? null;
                $id = (int) ($pair[$side.'_media_id'] ?? 0);
                if (($file instanceof UploadedFile) === ($id > 0)) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, ($index + 1).'번째 전후사진의 전/후 사진을 각각 1장씩 등록해 주세요.');
                }
                if ($file instanceof UploadedFile) {
                    $row[$side] = $file;

                    continue;
                }
                $media = $mediaById->get($id);
                if (! $media || $media->collection !== $collection || in_array($id, $usedIds, true)) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '전후사진은 해당 이벤트의 중복되지 않은 전/후 사진만 사용할 수 있습니다.');
                }
                $usedIds[] = $id;
                $row[$side] = $media;
            }
            $resolved[] = $row;
        }

        return $resolved;
    }
}
