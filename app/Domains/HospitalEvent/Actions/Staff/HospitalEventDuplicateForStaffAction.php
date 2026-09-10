<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalEvent\Dto\Staff\HospitalEventForStaffDetailDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventCreateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class HospitalEventDuplicateForStaffAction
{
    public function __construct(
        private readonly HospitalEventCreateForStaffQuery $query,
        private readonly HospitalEventPayloadResolver $payloadResolver,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly HospitalEventBeforeAfterPhotosSyncAction $beforeAfterPhotosAction,
        private readonly HospitalEventUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(AccountStaff $actor, HospitalEvent $sourceEvent, array $payload): array
    {
        Gate::authorize('view', $sourceEvent);
        Gate::authorize('create', HospitalEvent::class);

        $event = DB::transaction(function () use ($actor, $sourceEvent, $payload): HospitalEvent {
            $sourceEvent = HospitalEvent::query()->lockForUpdate()->findOrFail($sourceEvent->getKey());
            $sourceEvent->load(['thumbnailImage', 'eventPageImage', 'beforeAfterPhotos']);
            $data = $this->payloadResolver->normalizePersistPayload([
                ...$payload,
                'allow_status' => HospitalEvent::ALLOW_PENDING,
                'hospital_status' => HospitalEvent::HOSPITAL_STATUS_PUBLIC,
                'admin_status' => HospitalEvent::ADMIN_STATUS_NORMAL,
            ]);
            $data['manager_staff_id'] = $payload['manager_staff_id'] ?? $actor->getKey();
            $categorySync = $this->payloadResolver->resolveCategorySyncPayload(
                $payload['category_ids'] ?? [],
                (int) ($payload['primary_category_id'] ?? 0),
            );
            $doctorAssignments = $this->payloadResolver->resolveDoctorAssignments($payload, (int) $data['hospital_id']);
            $options = [];
            if ($categorySync['usage'] === CategoryUsage::USAGE_HOSPITAL_EVENT_TREATMENT) {
                $options = $this->payloadResolver->resolveOptions($payload, (string) $categorySync['usage']);
            }
            $data['has_options'] = $options !== [];

            $event = $this->query->create($data);

            $event->categories()->sync($categorySync['payload']);
            $this->payloadResolver->syncDoctorAssignments($event, $doctorAssignments);
            $this->payloadResolver->syncOptions($event, $options);
            $this->copyBeforeAfterPhotos($sourceEvent, $event, $payload);

            if (isset($payload['thumbnail_image'])) {
                $this->mediaAttachAction->attachOne($event, $payload['thumbnail_image'], HospitalEvent::COLLECTION_THUMBNAIL_IMAGE, 'hospital-event', 'thumbnail-image', true);
            } else {
                $this->copyMedia(
                    sourceMedia: $sourceEvent->thumbnailImage,
                    event: $event,
                    collection: HospitalEvent::COLLECTION_THUMBNAIL_IMAGE,
                    dirName: 'thumbnail-image',
                    missingMessage: '복제할 썸네일 이미지를 찾을 수 없습니다.',
                );
            }

            if ($event->event_type === HospitalEvent::TYPE_IMAGE) {
                if (isset($payload['event_page_image'])) {
                    $this->mediaAttachAction->attachOne($event, $payload['event_page_image'], HospitalEvent::COLLECTION_EVENT_PAGE_IMAGE, 'hospital-event', 'event-page-image', true);
                } else {
                    $this->copyMedia(
                        sourceMedia: $sourceEvent->eventPageImage,
                        event: $event,
                        collection: HospitalEvent::COLLECTION_EVENT_PAGE_IMAGE,
                        dirName: 'event-page-image',
                        missingMessage: '이미지 등록 이벤트는 이벤트 페이지 이미지를 등록해 주세요.',
                    );
                }
            }

            $event = $event->fresh([
                'hospital',
                'categories',
                'doctors',
                'options',
                'thumbnailImage',
                'eventPageImage',
                'beforeAfterPhotos',
            ]);

            $this->historyRecordAction->recordCreated($event);

            return $event;
        });

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_EVENT);

        return [
            'event' => HospitalEventForStaffDetailDto::fromModel($event->load([
                'hospital.businessRegistration',
                'categories',
                'doctors',
                'options',
                'thumbnailImage',
                'eventPageImage',
                'beforeAfterPhotos',
            ]))->toArray(),
        ];
    }

    private function copyBeforeAfterPhotos(HospitalEvent $source, HospitalEvent $event, array $payload): void
    {
        if ($event->event_type !== HospitalEvent::TYPE_TEXT) {
            $this->beforeAfterPhotosAction->execute($event, $payload);

            return;
        }
        $pairs = $payload['before_after_photos'] ?? $source->beforeAfterPhotos->groupBy('sort_order')->map(static fn ($photos): array => [
            'before_media_id' => $photos->firstWhere('collection', HospitalEvent::COLLECTION_BEFORE_PHOTO)?->id,
            'after_media_id' => $photos->firstWhere('collection', HospitalEvent::COLLECTION_AFTER_PHOTO)?->id,
        ])->values()->all();
        foreach ($this->beforeAfterPhotosAction->resolve($source, $pairs) as $index => $pair) {
            foreach ($pair as $side => $photo) {
                $collection = $side === 'before' ? HospitalEvent::COLLECTION_BEFORE_PHOTO : HospitalEvent::COLLECTION_AFTER_PHOTO;
                if ($photo instanceof Media) {
                    $this->copyMedia($photo, $event, $collection, 'before-after-photos/'.$side, '복제할 전후사진을 찾을 수 없습니다.', $index);
                } else {
                    $this->mediaAttachAction->attachOne($event, $photo, $collection, 'hospital-event', 'before-after-photos/'.$side, false, $index);
                }
            }
        }
    }

    private function copyMedia(?Media $sourceMedia, HospitalEvent $event, string $collection, string $dirName, string $missingMessage, ?int $sortOrder = null): void
    {
        if (! $sourceMedia) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, $missingMessage);
        }

        $disk = (string) ($sourceMedia->disk ?: 'public');
        if (! Storage::disk($disk)->exists($sourceMedia->path)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, $missingMessage);
        }

        $targetDir = "hospital-event/{$event->getKey()}/{$dirName}";
        Storage::disk($disk)->makeDirectory($targetDir);

        $targetPath = $this->targetPath($targetDir, $sourceMedia->path);
        app(\App\Domains\Common\Media\Services\MediaFileLifecycle::class)->stage(
            $disk,
            $targetPath,
            array_map(fn (string $path): string => $this->targetPath($targetDir, $path), $sourceMedia->variantPaths()),
        );
        if (! Storage::disk($disk)->copy($sourceMedia->path, $targetPath)) {
            throw new \RuntimeException('Event media copy failed.');
        }

        $metadata = $sourceMedia->metadata ?? [];
        $metadata['variants'] = $this->copyVariants($disk, $targetDir, $metadata['variants'] ?? []);

        Media::query()->create([
            'model_type' => $event::class,
            'model_id' => $event->getKey(),
            'collection' => $collection,
            'disk' => $disk,
            'path' => $targetPath,
            'mime_type' => $sourceMedia->mime_type,
            'size' => $sourceMedia->size,
            'width' => $sourceMedia->width,
            'height' => $sourceMedia->height,
            'sort_order' => $sortOrder ?? (int) $sourceMedia->sort_order,
            'is_primary' => (bool) $sourceMedia->is_primary,
            'metadata' => $metadata,
        ]);
    }

    private function targetPath(string $targetDir, string $sourcePath): string
    {
        return "{$targetDir}/".basename($sourcePath);
    }

    private function copyVariants(string $disk, string $targetDir, mixed $variants): array
    {
        if (! is_array($variants)) {
            return [];
        }

        $copied = [];
        foreach ($variants as $name => $variant) {
            if (! is_array($variant) || ! isset($variant['path']) || ! is_string($variant['path'])) {
                continue;
            }

            if (! Storage::disk($disk)->exists($variant['path'])) {
                continue;
            }

            $targetPath = $this->targetPath($targetDir, $variant['path']);
            if (! Storage::disk($disk)->copy($variant['path'], $targetPath)) {
                throw new \RuntimeException('Event media variant copy failed.');
            }

            $copied[$name] = [
                ...$variant,
                'path' => $targetPath,
            ];
        }

        return $copied;
    }
}
