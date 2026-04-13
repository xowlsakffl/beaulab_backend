<?php

namespace App\Domains\BeautyExpert\Actions\Staff;

use App\Domains\BeautyExpert\Dto\Staff\BeautyExpertForStaffDetailDto;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use Illuminate\Support\Facades\Gate;

/**
 * BeautyExpertGetForStaffAction 역할 정의.
 * 뷰티 전문가 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class BeautyExpertGetForStaffAction
{
    public function execute(BeautyExpert $expert): array
    {
        Gate::authorize('view', $expert);

        $expert->load([
            'profileImage',
            'educationCertificateImages',
            'etcCertificateImages',
            'categories',
        ]);

        return [
            'expert' => BeautyExpertForStaffDetailDto::fromModel($expert)->toArray(),
        ];
    }
}
