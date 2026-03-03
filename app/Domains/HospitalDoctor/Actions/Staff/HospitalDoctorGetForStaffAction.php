<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\HospitalDoctor\Dto\Staff\HospitalDoctorForStaffDetailDto;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Support\Facades\Gate;

final class HospitalDoctorGetForStaffAction
{
    public function execute(HospitalDoctor $doctor): array
    {
        Gate::authorize('view', $doctor);

        $doctor->load([
            'profileImage',
            'licenseImage',
            'specialistCertificateImages',
            'educationCertificateImages',
            'etcCertificateImages',
        ]);

        return [
            'doctor' => HospitalDoctorForStaffDetailDto::fromModel($doctor)->toArray(),
        ];
    }
}
