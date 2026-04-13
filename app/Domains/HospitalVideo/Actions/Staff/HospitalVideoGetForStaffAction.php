<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\HospitalVideo\Dto\Staff\HospitalVideoForStaffDetailDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalVideoGetForStaffAction 역할 정의.
 * 병원 동영상 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalVideoGetForStaffAction
{
    public function execute(HospitalVideo $video): array
    {
        Gate::authorize('view', $video);

        $relations = [
            'hospital',
            'hospital.businessRegistration',
            'doctor',
            'thumbnailMedia',
            'videoFileMedia',
            'categories',
        ];

        return [
            'video' => HospitalVideoForStaffDetailDto::fromModel($video->load($relations))->toArray(),
        ];
    }
}
