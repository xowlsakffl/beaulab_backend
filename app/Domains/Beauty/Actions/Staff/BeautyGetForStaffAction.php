<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Dto\Staff\BeautyForStaffDetailDto;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use Illuminate\Support\Facades\Gate;

/**
 * BeautyGetForStaffAction 역할 정의.
 * 뷰티 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class BeautyGetForStaffAction
{
    /**
     * @param array<int, string> $include
     * @return array{beauty: array}
     */
    public function execute(Beauty $beauty, array $include = []): array
    {
        Gate::authorize('view', $beauty);

        $relations = ['logoMedia', 'galleryMedia', 'categories'];

        if (in_array('business_registration', $include, true)) {
            $relations[] = 'businessRegistration.certificateMedia';
        }

        if (in_array('account_beauties', $include, true)) {
            $relations[] = 'accountBeauties.roles';
        }

        if (in_array('experts', $include, true)) {
            Gate::authorize('viewAny', BeautyExpert::class);
            $relations[] = 'experts.profileImage';
        }

        if ($relations !== []) {
            $beauty->load($relations);
        }

        return [
            'beauty' => BeautyForStaffDetailDto::fromModel($beauty, $include)->toArray(),
        ];
    }
}
