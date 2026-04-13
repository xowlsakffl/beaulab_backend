<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Hospital\Dto\Staff\HospitalForStaffDetailDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * HospitalUpdateForStaffAction 역할 정의.
 * 병원 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction     $mediaAttachAction,
    ) {}

    /**
     * @return array{hospital: array}
     */
    public function execute(Hospital $hospital, array $payload): array
    {
        Gate::authorize('update', $hospital);

        Log::info('병원 정보 수정 실행', [
            'hospital_id' => $hospital->id,
        ]);

        $updated = DB::transaction(function () use ($hospital, $payload) {
            $updatedHospital = $this->query->update($hospital, $payload);

            $this->replaceMedia($updatedHospital, $payload);
            $this->updateBusinessRegistration($updatedHospital, $payload);
            if (array_key_exists('category_ids', $payload) && is_array($payload['category_ids'])) {
                $this->syncCategories($updatedHospital, $payload['category_ids']);
            }
            if (array_key_exists('feature_ids', $payload) && is_array($payload['feature_ids'])) {
                $this->syncFeatures($updatedHospital, $payload['feature_ids']);
            }

            return $updatedHospital->fresh();
        });

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel(
                $updated->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories', 'features']),
                ['business_registration'],
            )->toArray(),
        ];
    }

    private function replaceMedia(Hospital $hospital, array $payload): void
    {
        if (isset($payload['logo']) && $payload['logo'] instanceof UploadedFile) {
            $this->deleteCollectionMedia($hospital, 'logo');
            $this->mediaAttachAction->attachOne($hospital, $payload['logo'], 'logo', 'hospital', 'logo');
        } elseif (array_key_exists('existing_logo_id', $payload) && empty($payload['existing_logo_id'])) {
            $this->deleteCollectionMedia($hospital, 'logo');
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

    private function updateBusinessRegistration(Hospital $hospital, array $payload): void
    {
        $businessRegistration = $hospital->businessRegistration()->first();
        if (! $businessRegistration) {
            return;
        }

        $updates = [];
        foreach ([
                     'business_number',
                     'company_name',
                     'ceo_name',
                     'business_type',
                     'business_item',
                     'business_address',
                     'business_address_detail',
                     'issued_at',
                 ] as $field) {
            if (array_key_exists($field, $payload)) {
                $updates[$field] = $payload[$field];
            }
        }

        if ($updates !== []) {
            $businessRegistration->update($updates);
        }

        if (isset($payload['business_registration_file']) && $payload['business_registration_file'] instanceof UploadedFile) {
            $existingCertificate = $businessRegistration->certificateMedia()->first();
            if ($existingCertificate) {
                Storage::disk($existingCertificate->disk)->delete($existingCertificate->path);
                $existingCertificate->delete();
            }

            $this->mediaAttachAction->attachOne($businessRegistration, $payload['business_registration_file'], 'business_registration_file', 'hospital', 'business-registration');
        } elseif (array_key_exists('existing_business_registration_file_id', $payload) && empty($payload['existing_business_registration_file_id'])) {
            $existingCertificate = $businessRegistration->certificateMedia()->first();
            if ($existingCertificate) {
                Storage::disk($existingCertificate->disk)->delete($existingCertificate->path);
                $existingCertificate->delete();
            }
        }
    }

    private function deleteCollectionMedia(Hospital $hospital, string $collection): void
    {
        Media::query()
            ->for($hospital)
            ->collection($collection)
            ->get()
            ->each(function (Media $media): void {
                Storage::disk($media->disk)->delete($media->path);
                $media->delete();
            });
    }

    /**
     * @param array<int, int|string> $existingMediaIds
     * @param array<int, UploadedFile> $newFiles
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
                    Storage::disk($media->disk)->delete($media->path);
                    $media->delete();
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
     * @param array<int, string> $galleryOrder
     * @param array<int, UploadedFile> $newFiles
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
                    Storage::disk($media->disk)->delete($media->path);
                    $media->delete();
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
     * @param mixed $files
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
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(Hospital $hospital, array $categoryIds): void
    {
        $payload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                $categoryId => ['is_primary' => $index === 0],
            ])
            ->all();

        $hospital->categories()->sync($payload);
    }

    /**
     * @param array<int, int|string> $featureIds
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
}
