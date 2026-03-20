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

            return $updatedHospital->fresh();
        });

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel(
                $updated->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories']),
                ['business_registration'],
            )->toArray(),
        ];
    }

    private function replaceMedia(Hospital $hospital, array $payload): void
    {
        if (isset($payload['logo']) && $payload['logo'] instanceof UploadedFile) {
            $this->deleteCollectionMedia($hospital, 'logo');
            $this->mediaAttachAction->attachOne($hospital, $payload['logo'], 'logo', 'hospital', 'logo');
        }

        if (isset($payload['gallery']) && is_array($payload['gallery'])) {
            $galleryFiles = array_values(array_filter(
                $payload['gallery'],
                static fn ($file): bool => $file instanceof UploadedFile,
            ));

            if ($galleryFiles !== []) {
                $this->deleteCollectionMedia($hospital, 'gallery');
                $this->mediaAttachAction->attachMany($hospital, $galleryFiles, 'gallery', 'hospital', 'gallery', true);
                return;
            }
        }

        if (array_key_exists('existing_gallery_ids', $payload) && is_array($payload['existing_gallery_ids'])) {
            $this->syncExistingGallery($hospital, $payload['existing_gallery_ids']);
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
     * @param array<int, int|string> $mediaIds
     */
    private function syncExistingGallery(Hospital $hospital, array $mediaIds): void
    {
        $galleryMedia = Media::query()
            ->for($hospital)
            ->collection('gallery')
            ->ordered()
            ->get()
            ->keyBy(static fn (Media $media): int => (int) $media->id);

        $orderedMediaIds = collect($mediaIds)
            ->map(static fn (int|string $mediaId): int => (int) $mediaId)
            ->filter(static fn (int $mediaId): bool => $mediaId > 0 && $galleryMedia->has($mediaId))
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
            $media->setPrimary($index === 0);
        });
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
}
