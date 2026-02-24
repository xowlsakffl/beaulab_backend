<?php

namespace App\Domains\Doctor\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Doctor\Dto\Staff\DoctorForStaffDetailDto;
use App\Domains\Doctor\Models\Doctor;
use App\Domains\Doctor\Queries\Staff\DoctorUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class DoctorUpdateForStaffAction
{
    public function __construct(
        private readonly DoctorUpdateForStaffQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
    ) {}

    public function execute(Doctor $doctor, array $payload): array
    {
        Gate::authorize('update', $doctor);

        $doctor = DB::transaction(function () use ($doctor, $payload) {
            $updated = $this->query->update($doctor, $payload);
            $this->replaceMedia($updated, $payload);
            return $updated->fresh();
        });

        return [
            'doctor' => DoctorForStaffDetailDto::fromModel($doctor->load([
                'profileImage',
                'licenseImage',
                'specialistCertificateImages',
                'graduationCertificates',
                'etcCertificates',
            ]))->toArray(),
        ];
    }

    private function replaceMedia(Doctor $doctor, array $payload): void
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

        if (array_key_exists('graduation_certificate', $payload) && is_array($payload['graduation_certificate'])) {
            $this->deleteCollectionMedia($doctor, 'graduation_certificate');
            $this->mediaAttachAction->attachDoctorGraduationCertificates($doctor, $this->onlyFiles($payload['graduation_certificate']), 'doctor');
        }

        if (array_key_exists('etc_certificate', $payload) && is_array($payload['etc_certificate'])) {
            $this->deleteCollectionMedia($doctor, 'etc_certificate');
            $this->mediaAttachAction->attachDoctorEtcCertificates($doctor, $this->onlyFiles($payload['etc_certificate']), 'doctor');
        }
    }

    private function deleteCollectionMedia(Doctor $doctor, string $collection): void
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
