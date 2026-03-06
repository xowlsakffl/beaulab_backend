<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\HospitalDoctor\Dto\Staff\HospitalDoctorForStaffDetailDto;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalDoctor\Queries\Staff\HospitalDoctorCreateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalDoctorCreateForStaffAction
{
    public function __construct(
        private readonly HospitalDoctorCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction           $mediaAttachAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', HospitalDoctor::class);

        $doctor = DB::transaction(function () use ($payload) {
            $doctor = $this->query->create($payload);

            $this->attachMedia($doctor, $payload);

            return $doctor->fresh();
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

    private function attachMedia(HospitalDoctor $doctor, array $payload): void
    {
        $this->mediaAttachAction->attachOne($doctor, $payload['profile_image'] ?? null, 'profile_image', 'doctor', 'profile-image');
        $this->mediaAttachAction->attachOne($doctor, $payload['license_image'] ?? null, 'license_image', 'doctor', 'license-image');

        $this->mediaAttachAction->attachMany($doctor, $this->onlyFiles($payload['specialist_certificate_image'] ?? null), 'specialist_certificate_image', 'doctor', 'specialist-certificate-image');

        $this->mediaAttachAction->attachMany($doctor, $this->onlyFiles($payload['education_certificate_image'] ?? null), 'education_certificate_image', 'doctor', 'education-certificate-image');

        $this->mediaAttachAction->attachMany($doctor, $this->onlyFiles($payload['etc_certificate_image'] ?? null), 'etc_certificate_image', 'doctor', 'etc-certificate-image');
    }

    private function onlyFiles(mixed $files): array
    {
        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, static fn ($file): bool => $file instanceof UploadedFile));
    }
}
