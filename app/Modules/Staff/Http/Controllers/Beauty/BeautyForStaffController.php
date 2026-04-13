<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\Beauty;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Beauty\Actions\Staff\BeautyCreateForStaffAction;
use App\Domains\Beauty\Actions\Staff\BeautyDeleteForStaffAction;
use App\Domains\Beauty\Actions\Staff\BeautyGetForStaffAction;
use App\Domains\Beauty\Actions\Staff\BeautyListForStaffAction;
use App\Domains\Beauty\Actions\Staff\BeautyUpdateForStaffAction;
use App\Domains\Beauty\Models\Beauty;
use App\Modules\Staff\Http\Requests\Beauty\BeautyCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Beauty\BeautyGetForStaffRequest;
use App\Modules\Staff\Http\Requests\Beauty\BeautyListForStaffRequest;
use App\Modules\Staff\Http\Requests\Beauty\BeautyUpdateForStaffRequest;

/**
 * BeautyForStaffController 역할 정의.
 * 뷰티 도메인의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class BeautyForStaffController extends Controller
{
    /**
     * GET /api/v1/staff/beauties
     * (Beaulab) Staff 전용 뷰티 목록
     */
    public function getBeautiesForStaff(
        BeautyListForStaffRequest $request,
        BeautyListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    /**
     * GET /api/v1/staff/beauties/{beauty}
     * (Beaulab) Staff 전용 뷰티 단건 조회
     */
    public function getBeautyForStaff(
        Beauty $beauty,
        BeautyGetForStaffRequest $request,
        BeautyGetForStaffAction $action,
    ) {
        $result = $action->execute($beauty, $request->filters()['include']);

        return ApiResponse::success($result['beauty'] ?? $result);
    }

    /**
     * POST /api/v1/staff/beauties
     * (Beaulab) Staff 전용 뷰티 생성
     */
    public function storeBeautyForStaff(
        BeautyCreateForStaffRequest $request,
        BeautyCreateForStaffAction $action,
    ) {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['beauty'] ?? $result);
    }

    /**
     * PATCH /api/v1/staff/beauties/{beauty}
     * (Beaulab) Staff 전용 뷰티 수정
     */
    public function updateBeautyForStaff(
        Beauty $beauty,
        BeautyUpdateForStaffRequest $request,
        BeautyUpdateForStaffAction $action,
    ) {
        $result = $action->execute($beauty, $request->validated());

        return ApiResponse::success($result['beauty'] ?? $result);
    }

    /**
     * DELETE /api/v1/staff/beauties/{beauty}
     * (Beaulab) Staff 전용 뷰티 삭제
     */
    public function deleteBeautyForStaff(
        Beauty $beauty,
        BeautyDeleteForStaffAction $action,
    ) {
        $result = $action->execute($beauty);

        return ApiResponse::success($result);
    }
}
