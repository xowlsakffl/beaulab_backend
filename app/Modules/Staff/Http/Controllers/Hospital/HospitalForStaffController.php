<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\Hospital;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Hospital\Actions\Staff\HospitalAllowStatusUpdateForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalCheckBusinessNumberForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalCheckNameForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalCreateForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalDeleteForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalGetForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalListForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalOperationHistoriesForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalStatusUpdateForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalSummaryForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalUpdateForStaffAction;
use App\Domains\Hospital\Models\Hospital;
use App\Modules\Staff\Http\Requests\Hospital\HospitalAllowStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalCheckBusinessNumberForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalCheckNameForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalGetForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalListForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalUpdateForStaffRequest;

/**
 * HospitalForStaffController 역할 정의.
 * 병원 도메인의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class HospitalForStaffController extends Controller
{
    /**
     * GET /api/v1/staff/hospitals
     * (Beaulab) Staff 전용 병원 목록
     */
    public function getHospitalsForStaff(
        HospitalListForStaffRequest $request,
        HospitalListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    /**
     * GET /api/v1/staff/hospitals/summary
     * (Beaulab) Staff 전용 병의원 목록 상단 집계
     */
    public function getHospitalSummaryForStaff(HospitalSummaryForStaffAction $action)
    {
        return ApiResponse::success($action->execute());
    }

    /**
     * GET /api/v1/staff/hospitals/{hospital}
     * (Beaulab) Staff 전용 병원 단건 조회
     */
    public function getHospitalForStaff(
        Hospital $hospital,
        HospitalGetForStaffRequest $request,
        HospitalGetForStaffAction $action,
    ) {
        $result = $action->execute($hospital, $request->filters()['include']);

        return ApiResponse::success($result['hospital']);
    }

    public function getHospitalOperationHistoriesForStaff(
        Hospital $hospital,
        HospitalGetForStaffRequest $request,
        HospitalOperationHistoriesForStaffAction $action,
    ) {
        $result = $action->execute($hospital, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    /**
     * POST /api/v1/staff/hospitals
     * (Beaulab) Staff 전용 병원 생성
     */
    public function createHospitalForStaff(
        HospitalCreateForStaffRequest $request,
        HospitalCreateForStaffAction $action,
    ) {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['hospital']);
    }

    /**
     * POST /api/v1/staff/hospitals/check-name
     * (Beaulab) Staff 전용 병의원명 중복 확인
     */
    public function checkHospitalNameDuplicateForStaff(
        HospitalCheckNameForStaffRequest $request,
        HospitalCheckNameForStaffAction $action,
    ) {
        $result = $action->execute((string) $request->validated('name'));

        return ApiResponse::success($result);
    }

    /**
     * POST /api/v1/staff/hospitals/check-business-number
     * (Beaulab) Staff 전용 병의원 사업자등록번호 중복 확인
     */
    public function checkHospitalBusinessNumberDuplicateForStaff(
        HospitalCheckBusinessNumberForStaffRequest $request,
        HospitalCheckBusinessNumberForStaffAction $action,
    ) {
        $result = $action->execute((string) $request->validated('business_number'));

        return ApiResponse::success($result);
    }

    /**
     * PATCH /api/v1/staff/hospitals/{hospital}
     * (Beaulab) Staff 전용 병원 수정
     */
    public function updateHospitalForStaff(
        Hospital $hospital,
        HospitalUpdateForStaffRequest $request,
        HospitalUpdateForStaffAction $action,
    ) {
        $result = $action->execute($hospital, $request->validated());

        return ApiResponse::success($result['hospital']);
    }

    /**
     * PATCH /api/v1/staff/hospitals/{hospital}/status
     * (Beaulab) Staff 전용 병원 상태 변경
     */
    public function updateHospitalStatusForStaff(
        Hospital $hospital,
        HospitalStatusUpdateForStaffRequest $request,
        HospitalStatusUpdateForStaffAction $action,
    ) {
        $result = $action->execute($hospital, $request->validated());

        return ApiResponse::success($result['hospital']);
    }

    /**
     * PATCH /api/v1/staff/hospitals/allow-status
     * (Beaulab) Staff 전용 병원 검수상태 변경
     */
    public function updateHospitalAllowStatusForStaff(
        HospitalAllowStatusUpdateForStaffRequest $request,
        HospitalAllowStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    /**
     * DELETE /api/v1/staff/hospitals/{hospital}
     * (Beaulab) Staff 전용 병원 삭제
     */
    public function deleteHospitalForStaff(
        Hospital $hospital,
        HospitalDeleteForStaffAction $action,
    ) {
        $result = $action->execute($hospital);

        return ApiResponse::success($result);
    }
}
