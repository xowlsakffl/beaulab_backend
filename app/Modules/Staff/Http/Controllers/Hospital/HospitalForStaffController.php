<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\Hospital;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Hospital\Actions\Staff\HospitalCreateForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalDeleteForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalGetForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalListForStaffAction;
use App\Domains\Hospital\Actions\Staff\HospitalUpdateForStaffAction;
use App\Domains\Hospital\Models\Hospital;
use App\Modules\Staff\Http\Requests\Hospital\HospitalCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalGetForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalListForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalUpdateForStaffRequest;

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
     * GET /api/v1/staff/hospitals/{hospital}
     * (Beaulab) Staff 전용 병원 단건 조회
     */
    public function getHospitalForStaff(
        Hospital $hospital,
        HospitalGetForStaffRequest $request,
        HospitalGetForStaffAction $action,
    ) {
        $result = $action->execute($hospital, $request->filters()['include']);

        return ApiResponse::success($result['hospital'] ?? $result);
    }

    /**
     * POST /api/v1/staff/hospitals
     * (Beaulab) Staff 전용 병원 생성
     */
    public function storeHospitalForStaff(
        HospitalCreateForStaffRequest $request,
        HospitalCreateForStaffAction $action,
    ) {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['hospital'] ?? $result);
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

        return ApiResponse::success($result['hospital'] ?? $result);
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
