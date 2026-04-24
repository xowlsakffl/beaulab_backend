<?php

namespace App\Modules\User\Http\Controllers\HospitalReview;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalReview\Actions\User\HospitalReviewCreateForUserAction;
use App\Modules\User\Http\Requests\HospitalReview\HospitalReviewCreateForUserRequest;

/**
 * HospitalReviewCreateForUserController 역할 정의.
 * 병의원 후기 도메인의 HTTP 컨트롤러로, 요청 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class HospitalReviewCreateForUserController extends Controller
{
    public function createHospitalReviewForUser(HospitalReviewCreateForUserRequest $request, HospitalReviewCreateForUserAction $action)
    {
        $result = $action->execute($request->user(), $request->validated());

        return ApiResponse::success($result['hospital_review'] ?? $result);
    }
}
