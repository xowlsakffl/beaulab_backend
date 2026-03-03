<?php

namespace App\Domains\Doctor\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachAction;
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
        private readonly MediaAttachAction                 $mediaAttachAction,
    ) {}

    public function execute(HospitalDoctor $doctor, array $payload): array
    {
        Gate::authorize('update', $doctor);

        $doctor = DB::transaction(function () use ($doctor, $payload) {
            $updated = $this->query->update($doctor, $payload);
            $this->replaceMedia($updated, $payload);
            return $updated->fresh();
        });

        return [
            'doctor' => HospitalDoctorForStaffDetailDto::fromModel($doctor->load([
                'profileImage',
                'licenseImage',
                'specialistCertificateImages',
                'educationCertificateImages',
                'etcCertificateImages',
            ]))->toArray(),
        ];
    }

    private function replaceMedia(HospitalDoctor $doctor, array $payload): void
    {
        if (($payload['profile_image'] ?? null) instanceof UploadedFile) {
            $this->deleteCollectionMedia($doctor, 'profile_image');
            $this->mediaAttachAction->attachDoctorProfileImage($doctor, $payload['profile_image'], 'doctor');
        }

        if (($payload['license_image'] ?? null) instanceof UploadedFile) {
            $this->deleteCollectionMedia($doctor, 'license_image');
            $this->mediaAttachAction->attachDoctorLicenseImage($doctor, $payload['license_image'], 'doctor');
        }

        if (array_key_exists('specialist_certificate_image', $payload) && is_array($payload['specialist_certificate_image'])) {
            $this->deleteCollectionMedia($doctor, 'specialist_certificate_image');
            $this->mediaAttachAction->attachDoctorSpecialistCertificateImages($doctor, $this->onlyFiles($payload['specialist_certificate_image']), 'doctor');
        }

        if (array_key_exists('education_certificate_image', $payload) && is_array($payload['education_certificate_image'])) {
            $this->deleteCollectionMedia($doctor, 'education_certificate_image');
            $this->mediaAttachAction->attachDoctorEducationCertificateImages($doctor, $this->onlyFiles($payload['education_certificate_image']), 'doctor');
        }

        if (array_key_exists('etc_certificate_image', $payload) && is_array($payload['etc_certificate_image'])) {
            $this->deleteCollectionMedia($doctor, 'etc_certificate_image');
            $this->mediaAttachAction->attachDoctorEtcCertificateImages($doctor, $this->onlyFiles($payload['etc_certificate_image']), 'doctor');
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
}
