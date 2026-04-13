<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Hospital\Dto\Staff\HospitalForStaffDetailDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalGetForStaffAction 역할 정의.
 * 병원 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalGetForStaffAction
{
    /**
     * @param array<int, string> $include
     * @return array{hospital: array}
     */
    public function execute(Hospital $hospital, array $include = []): array
    {
        Gate::authorize('view', $hospital);

        $relations = ['logoMedia', 'galleryMedia', 'categories', 'features'];

        if (in_array('business_registration', $include, true)) {
            $relations[] = 'businessRegistration.certificateMedia';
        }

        if (in_array('account_hospitals', $include, true)) {
            $relations[] = 'accountHospitals.roles';
        }

        if (in_array('doctors', $include, true)) {
            Gate::authorize('viewAny', HospitalDoctor::class);
            $relations[] = 'doctors';
        }

        if ($relations !== []) {
            $hospital->load($relations);
        }

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel($hospital, $include)->toArray(),
        ];
    }
}
