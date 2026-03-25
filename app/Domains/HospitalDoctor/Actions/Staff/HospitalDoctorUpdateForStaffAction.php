<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Common\Models\Media\Media;
use App\Domains\HospitalDoctor\Dto\Staff\HospitalDoctorForStaffDetailDto;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalDoctor\Queries\Staff\HospitalDoctorUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class HospitalDoctorUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalDoctorUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction           $mediaAttachAction,
    ) {}

    public function execute(HospitalDoctor $doctor, array $payload): array
    {
        Gate::authorize('update', $doctor);

        $doctor = DB::transaction(function () use ($doctor, $payload) {
            $updated = $this->query->update($doctor, $payload);
            $this->replaceMedia($updated, $payload);
            if (array_key_exists('category_ids', $payload) && is_array($payload['category_ids'])) {
                $this->syncCategories($updated, $payload['category_ids']);
            }
            return $updated->fresh();
        });

        return [
            'doctor' => HospitalDoctorForStaffDetailDto::fromModel($doctor->load([
                'hospital.businessRegistration',
                'profileImage',
                'licenseImage',
                'specialistCertificateImages',
                'educationCertificateImages',
                'etcCertificateImages',
                'categories',
            ]))->toArray(),
        ];
    }

    private function replaceMedia(HospitalDoctor $doctor, array $payload): void
    {
        if (($payload['profile_image'] ?? null) instanceof UploadedFile) {
            $this->deleteCollectionMedia($doctor, 'profile_image');
            $this->mediaAttachAction->attachOne($doctor, $payload['profile_image'], 'profile_image', 'doctor', 'profile-image');
        } elseif (array_key_exists('existing_profile_image_id', $payload) && empty($payload['existing_profile_image_id'])) {
            $this->deleteCollectionMedia($doctor, 'profile_image');
        }

        if (($payload['license_image'] ?? null) instanceof UploadedFile) {
            $this->deleteCollectionMedia($doctor, 'license_image');
            $this->mediaAttachAction->attachOne($doctor, $payload['license_image'], 'license_image', 'doctor', 'license-image');
        } elseif (array_key_exists('existing_license_image_id', $payload) && empty($payload['existing_license_image_id'])) {
            $this->deleteCollectionMedia($doctor, 'license_image');
        }

        if (($payload['specialist_certificate_image'] ?? null) instanceof UploadedFile) {
            $this->deleteCollectionMedia($doctor, 'specialist_certificate_image');
            $this->mediaAttachAction->attachOne($doctor, $payload['specialist_certificate_image'], 'specialist_certificate_image', 'doctor', 'specialist-certificate-image');
        } elseif (array_key_exists('existing_specialist_certificate_image_id', $payload) && empty($payload['existing_specialist_certificate_image_id'])) {
            $this->deleteCollectionMedia($doctor, 'specialist_certificate_image');
        }

        if (array_key_exists('existing_education_certificate_image_ids', $payload) || array_key_exists('education_certificate_image', $payload)) {
            $this->syncMediaCollection(
                $doctor,
                'education_certificate_image',
                'education-certificate-image',
                $payload['existing_education_certificate_image_ids'] ?? [],
                $this->onlyFiles($payload['education_certificate_image'] ?? []),
            );
        }

        if (array_key_exists('existing_etc_certificate_image_ids', $payload) || array_key_exists('etc_certificate_image', $payload)) {
            $this->syncMediaCollection(
                $doctor,
                'etc_certificate_image',
                'etc-certificate-image',
                $payload['existing_etc_certificate_image_ids'] ?? [],
                $this->onlyFiles($payload['etc_certificate_image'] ?? []),
            );
        }
    }

    private function deleteCollectionMedia(HospitalDoctor $doctor, string $collection): void
    {
        Media::query()->for($doctor)->collection($collection)->get()->each(function (Media $media): void {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        });
    }

    private function onlyFiles(array $files): array
    {
        return array_values(array_filter($files, static fn ($file): bool => $file instanceof UploadedFile));
    }

    /**
     * @param array<int, int|string> $existingMediaIds
     * @param array<int, UploadedFile> $newFiles
     */
    private function syncMediaCollection(
        HospitalDoctor $doctor,
        string $collection,
        string $dirName,
        array $existingMediaIds,
        array $newFiles,
    ): void {
        $currentMedia = Media::query()
            ->for($doctor)
            ->collection($collection)
            ->ordered()
            ->get()
            ->keyBy(static fn (Media $media): int => (int) $media->id);

        $keptMediaIds = collect($existingMediaIds)
            ->map(static fn (int|string $mediaId): int => (int) $mediaId)
            ->filter(static fn (int $mediaId): bool => $mediaId > 0 && $currentMedia->has($mediaId))
            ->unique()
            ->values();

        $deletedMediaIds = $currentMedia->keys()->diff($keptMediaIds);

        if ($deletedMediaIds->isNotEmpty()) {
            Media::query()
                ->whereIn('id', $deletedMediaIds->all())
                ->get()
                ->each(function (Media $media): void {
                    Storage::disk($media->disk)->delete($media->path);
                    $media->delete();
                });
        }

        $keptMediaIds->each(function (int $mediaId, int $index) use ($currentMedia): void {
            $media = $currentMedia->get($mediaId);

            if (! $media) {
                return;
            }

            $media->setSortOrder($index);
        });

        $baseSortOrder = $keptMediaIds->count();
        foreach (array_values($newFiles) as $index => $file) {
            $this->mediaAttachAction->attachOne(
                $doctor,
                $file,
                $collection,
                'doctor',
                $dirName,
                false,
                $baseSortOrder + $index,
            );
        }
    }

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(HospitalDoctor $doctor, array $categoryIds): void
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

        $doctor->categories()->sync($payload);
    }
}
