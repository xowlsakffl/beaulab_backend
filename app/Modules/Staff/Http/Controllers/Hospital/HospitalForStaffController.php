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
use App\Domains\Hospital\Queries\Staff\HospitalBusinessNumberExistsForStaffQuery;
use App\Domains\Hospital\Queries\Staff\HospitalNameExistsForStaffQuery;
use App\Modules\Staff\Http\Requests\Hospital\HospitalCheckBusinessNumberForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalCheckNameForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalGetForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalListForStaffRequest;
use App\Modules\Staff\Http\Requests\Hospital\HospitalUpdateForStaffRequest;
use Illuminate\Support\Facades\Gate;

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
     * GET /api/v1/staff/hospitals/{hospital}/edit
     * (Beaulab) Staff 전용 병원 수정용 단건 조회
     */
    public function getHospitalForEditForStaff(
        Hospital $hospital,
        HospitalGetForStaffRequest $request,
        HospitalGetForStaffAction $action,
    ) {
        $result = $action->execute($hospital, $request->filters()['include'], 'update');

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
     * POST /api/v1/staff/hospitals/check-name
     * (Beaulab) Staff 전용 병의원명 중복 확인
     */
    public function checkHospitalNameDuplicateForStaff(
        HospitalCheckNameForStaffRequest $request,
        HospitalNameExistsForStaffQuery $query,
    ) {
        Gate::authorize('create', Hospital::class);

        $payload = $request->validated();
        $exists = $query->exists($payload['name']);

        return ApiResponse::success([
            'exists' => $exists,
            'available' => ! $exists,
        ]);
    }

    /**
     * POST /api/v1/staff/hospitals/check-business-number
     * (Beaulab) Staff 전용 병의원 사업자등록번호 중복 확인
     */
    public function checkHospitalBusinessNumberDuplicateForStaff(
        HospitalCheckBusinessNumberForStaffRequest $request,
        HospitalBusinessNumberExistsForStaffQuery $query,
    ) {
        Gate::authorize('create', Hospital::class);

        $payload = $request->validated();
        $exists = $query->exists($payload['business_number']);

        return ApiResponse::success([
            'exists' => $exists,
            'available' => ! $exists,
            'business_number' => $payload['business_number'],
        ]);
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
