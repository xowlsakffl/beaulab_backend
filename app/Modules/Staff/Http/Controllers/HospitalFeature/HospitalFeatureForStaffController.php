<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalFeature;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalFeature\Actions\Staff\HospitalFeatureListForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalFeature\HospitalFeatureListForStaffRequest;

/**
 * HospitalFeatureForStaffController 역할 정의.
 * 병원 특징 도메인의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class HospitalFeatureForStaffController extends Controller
{
    public function getHospitalFeaturesForStaff(
        HospitalFeatureListForStaffRequest $request,
        HospitalFeatureListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }
}
