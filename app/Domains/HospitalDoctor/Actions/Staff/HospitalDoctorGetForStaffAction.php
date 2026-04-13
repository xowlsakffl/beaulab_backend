<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\HospitalDoctor\Dto\Staff\HospitalDoctorForStaffDetailDto;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalDoctorGetForStaffAction 역할 정의.
 * 병원 의사 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalDoctorGetForStaffAction
{
    public function execute(HospitalDoctor $doctor): array
    {
        Gate::authorize('view', $doctor);

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
