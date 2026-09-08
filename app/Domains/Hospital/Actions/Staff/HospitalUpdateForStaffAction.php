<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Hospital\Dto\Staff\HospitalForStaffDetailDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalUpdateForStaffAction 역할 정의.
 * 병원 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly HospitalBusinessRegistrationUpdateForStaffAction $businessRegistrationUpdateAction,
        private readonly HospitalUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    /**
     * @return array{hospital: array}
     */
    public function execute(Hospital $hospital, array $payload): array
    {
        Gate::authorize('update', $hospital);
        if (array_key_exists('allow_status', $payload)) {
            Gate::authorize('updateStatus', $hospital);
        }

        $beforeHistory = $this->historyRecordAction->capture($hospital);

        $updated = DB::transaction(function () use (
            $hospital,
            $payload,
            $beforeHistory,
        ) {
            $updatedHospital = $this->query->update($hospital, $payload);

            $this->replaceMedia($updatedHospital, $payload);
            $this->businessRegistrationUpdateAction->execute($updatedHospital, $payload);
            if (array_key_exists('category_ids', $payload) && is_array($payload['category_ids'])) {
                $this->syncCategories($updatedHospital, $payload['category_ids']);
            }
            if (array_key_exists('feature_ids', $payload) && is_array($payload['feature_ids'])) {
                $this->syncFeatures($updatedHospital, $payload['feature_ids']);
            }
            $this->historyRecordAction->recordUpdated($updatedHospital, $beforeHistory);

            return $updatedHospital->fresh();
        });

        if ($this->shouldForgetSummary($payload)) {
            StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL);
        }

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel(
                $updated
                    ->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories', 'features', 'operationHistories.actor'])
                    ->loadNewEventDBCount()
            )->toArray(),
        ];
    }

    private function replaceMedia(Hospital $hospital, array $payload): void
    {
        if (isset($payload['logo']) && $payload['logo'] instanceof UploadedFile) {
            $this->mediaAttachAction->deleteCollectionMedia($hospital, 'logo');
            $this->mediaAttachAction->attachOne($hospital, $payload['logo'], 'logo', 'hospital', 'logo');
        } elseif (array_key_exists('existing_logo_id', $payload) && empty($payload['existing_logo_id'])) {
            $this->mediaAttachAction->deleteCollectionMedia($hospital, 'logo');
        }

        if (array_key_exists('gallery_order', $payload)) {
            $this->syncGalleryByOrder(
                $hospital,
                $payload['gallery_order'] ?? [],
                $this->onlyFiles($payload['gallery'] ?? []),
            );
        } elseif (array_key_exists('existing_gallery_ids', $payload) || array_key_exists('gallery', $payload)) {
            $this->syncGallery(
                $hospital,
                $payload['existing_gallery_ids'] ?? [],
                $this->onlyFiles($payload['gallery'] ?? []),
            );
        }
    }

    /**
     * @param  array<int, int|string>  $existingMediaIds
     * @param  array<int, UploadedFile>  $newFiles
     */
    private function syncGallery(Hospital $hospital, array $existingMediaIds, array $newFiles): void
    {
        $galleryMedia = Media::query()
            ->for($hospital)
            ->collection('gallery')
            ->ordered()
            ->get()
            ->keyBy(static fn (Media $media): int => (int) $media->id);

        $orderedMediaIds = collect($existingMediaIds)
            ->map(static fn (int|string $mediaId): int => (int) $mediaId)
            ->filter(static fn (int $mediaId): bool => $mediaId > 0 && $galleryMedia->has($mediaId))
            ->unique()
            ->values();

        $deletedMediaIds = $galleryMedia->keys()->diff($orderedMediaIds);

        if ($deletedMediaIds->isNotEmpty()) {
            Media::query()
                ->whereIn('id', $deletedMediaIds->all())
                ->get()
                ->each(function (Media $media): void {
                    app(\App\Domains\Common\Media\Actions\MediaAttachDeleteAction::class)->delete($media);
                });
        }

        $orderedMediaIds->each(function (int $mediaId, int $index) use ($galleryMedia): void {
            $media = $galleryMedia->get($mediaId);

            if (! $media) {
                return;
            }

            $media->setSortOrder($index);
        });

        $baseSortOrder = $orderedMediaIds->count();
        foreach (array_values($newFiles) as $index => $file) {
            $this->mediaAttachAction->attachOne(
                $hospital,
                $file,
                'gallery',
                'hospital',
                'gallery',
                false,
                $baseSortOrder + $index,
            );
        }

        Media::query()
            ->for($hospital)
            ->collection('gallery')
            ->ordered()
            ->get()
            ->values()
            ->each(function (Media $media, int $index): void {
                $media->setSortOrder($index);
                $media->setPrimary($index === 0);
            });
    }

    /**
     * @param  array<int, string>  $galleryOrder
     * @param  array<int, UploadedFile>  $newFiles
     */
    private function syncGalleryByOrder(Hospital $hospital, array $galleryOrder, array $newFiles): void
    {
        $galleryMedia = Media::query()
            ->for($hospital)
            ->collection('gallery')
            ->ordered()
            ->get()
            ->keyBy(static fn (Media $media): int => (int) $media->id);

        $orderedEntries = [];
        $keptExistingIds = [];

        foreach ($galleryOrder as $token) {
            if (! is_string($token) || ! preg_match('/^(existing|new):(\d+)$/', $token, $matches)) {
                continue;
            }

            $entryType = $matches[1];
            $entryValue = (int) $matches[2];

            if ($entryType === 'existing') {
                $media = $galleryMedia->get($entryValue);
                if (! $media) {
                    continue;
                }

                $keptExistingIds[] = $entryValue;
                $orderedEntries[] = [
                    'type' => 'existing',
                    'media' => $media,
                ];

                continue;
            }

            $file = $newFiles[$entryValue] ?? null;
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $orderedEntries[] = [
                'type' => 'new',
                'file' => $file,
            ];
        }

        $deletedMediaIds = $galleryMedia->keys()->diff($keptExistingIds);

        if ($deletedMediaIds->isNotEmpty()) {
            Media::query()
                ->whereIn('id', $deletedMediaIds->all())
                ->get()
                ->each(function (Media $media): void {
                    app(\App\Domains\Common\Media\Actions\MediaAttachDeleteAction::class)->delete($media);
                });
        }

        foreach ($orderedEntries as $index => $entry) {
            if ($entry['type'] === 'existing') {
                /** @var Media $media */
                $media = $entry['media'];
                $media->setSortOrder($index);
                $media->setPrimary($index === 0);

                continue;
            }

            /** @var UploadedFile $file */
            $file = $entry['file'];
            $this->mediaAttachAction->attachOne(
                $hospital,
                $file,
                'gallery',
                'hospital',
                'gallery',
                $index === 0,
                $index,
            );
        }

        Media::query()
            ->for($hospital)
            ->collection('gallery')
            ->ordered()
            ->get()
            ->values()
            ->each(function (Media $media, int $index): void {
                $media->setSortOrder($index);
                $media->setPrimary($index === 0);
            });
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function onlyFiles(mixed $files): array
    {
        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, static fn ($file): bool => $file instanceof UploadedFile));
    }

    /**
     * @param  array<int, int|string>  $categoryIds
     */
    private function syncCategories(Hospital $hospital, array $categoryIds): void
    {
        $payload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId): array => [
                $categoryId => ['is_primary' => false],
            ])
            ->all();

        $hospital->categories()->sync($payload);
    }

    /**
     * @param  array<int, int|string>  $featureIds
     */
    private function syncFeatures(Hospital $hospital, array $featureIds): void
    {
        $payload = collect($featureIds)
            ->map(static fn (int|string $featureId): int => (int) $featureId)
            ->filter(static fn (int $featureId): bool => $featureId > 0)
            ->unique()
            ->values()
            ->all();

        $hospital->features()->sync($payload);
    }

    private function shouldForgetSummary(array $payload): bool
    {
        return array_key_exists('allow_status', $payload);
    }
}
