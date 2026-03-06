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

            return $updatedHospital->fresh();
        });

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel(
                $updated->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia']),
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
            }
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
}
