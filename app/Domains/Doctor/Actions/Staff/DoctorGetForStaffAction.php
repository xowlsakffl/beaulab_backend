<?php

namespace App\Domains\Doctor\Actions\Staff;

use App\Domains\Doctor\Dto\Staff\DoctorForStaffDetailDto;
use App\Domains\Doctor\Models\Doctor;
use Illuminate\Support\Facades\Gate;

final class DoctorGetForStaffAction
{
    public function execute(Doctor $doctor): array
    {
        Gate::authorize('view', $doctor);

        $doctor->load([
            'profileImage',
            'licenseImage',
            'specialistCertificateImages',
            'graduationCertificateImages',
            'etcCertificateImages',
        ]);

        return [
            'doctor' => DoctorForStaffDetailDto::fromModel($doctor)->toArray(),
        ];
    }
}
