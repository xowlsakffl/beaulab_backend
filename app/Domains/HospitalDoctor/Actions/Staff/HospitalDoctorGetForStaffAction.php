<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\HospitalDoctor\Dto\Staff\HospitalDoctorForStaffDetailDto;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Support\Facades\Gate;

final class HospitalDoctorGetForStaffAction
{
    public function execute(HospitalDoctor $doctor, string $ability = 'view'): array
    {
        Gate::authorize($ability, $doctor);

        $doctor->load([
            'hospital.businessRegistration',
            'profileImage',
            'licenseImage',
            'specialistCertificateImages',
            'educationCertificateImages',
            'etcCertificateImages',
            'categories',
        ]);

        return [
            'doctor' => HospitalDoctorForStaffDetailDto::fromModel($doctor)->toArray(),
        ];
    }
}
