<?php

namespace App\Domains\Doctor\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Doctor\Dto\Staff\DoctorForStaffDetailDto;
use App\Domains\Doctor\Models\Doctor;
use App\Domains\Doctor\Queries\Staff\DoctorCreateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DoctorCreateForStaffAction
{
    public function __construct(
        private readonly DoctorCreateForStaffQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', Doctor::class);

        $doctor = DB::transaction(function () use ($payload) {
            $doctor = $this->query->create($payload);

            $this->attachMedia($doctor, $payload);

            return $doctor->fresh();
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

    private function attachMedia(Doctor $doctor, array $payload): void
    {
        $this->mediaAttachAction->attachDoctorProfileImage($doctor, $payload['profile_image'] ?? null, 'doctor');
        $this->mediaAttachAction->attachDoctorLicenseImage($doctor, $payload['license_image'] ?? null, 'doctor');

        $this->mediaAttachAction->attachDoctorSpecialistCertificateImages(
            $doctor,
            $this->onlyFiles($payload['specialist_certificate_image'] ?? null),
            'doctor',
        );

        $this->mediaAttachAction->attachDoctorGraduationCertificates(
            $doctor,
            $this->onlyFiles($payload['graduation_certificate'] ?? null),
            'doctor',
        );

        $this->mediaAttachAction->attachDoctorEtcCertificates(
            $doctor,
            $this->onlyFiles($payload['etc_certificate'] ?? null),
            'doctor',
        );
    }

    private function onlyFiles(mixed $files): array
    {
        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, static fn ($file): bool => $file instanceof UploadedFile));
    }
}
