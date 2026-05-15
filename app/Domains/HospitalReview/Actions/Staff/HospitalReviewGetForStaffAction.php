<?php

namespace App\Domains\HospitalReview\Actions\Staff;

use App\Domains\HospitalReview\Dto\Staff\HospitalReviewForStaffDetailDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalReviewGetForStaffAction 역할 정의.
 * 병원후기 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
class HospitalReviewGetForStaffAction
{
    public function execute(HospitalReview $review, array $filters = []): array
    {
        Gate::authorize('view', $review);

        $review->load([
            'author',
            'hospital.businessRegistration',
            'doctor',
            'categories',
            'beforeImages',
            'afterImages',
        ]);

        return [
            'review' => HospitalReviewForStaffDetailDto::fromModel($review)->toArray(),
        ];
    }
}
