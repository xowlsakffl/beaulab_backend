<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers\Hospital;

use App\Common\Http\ApiResponse;
use App\Domains\Hospital\Actions\Admin\HospitalCreateForStaffAction;
use App\Domains\Hospital\Actions\Admin\HospitalDeleteForStaffAction;
use App\Domains\Hospital\Actions\Admin\HospitalListForStaffAction;
use App\Domains\Hospital\Actions\Admin\HospitalUpdateForStaffAction;
use App\Domains\Hospital\Actions\Admin\UpdateHospital;
use App\Domains\Hospital\Dto\Admin\HospitalUpsert;
use App\Domains\Hospital\Models\Hospital;
use App\Modules\Admin\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\Hospital\HospitalCreateForStaffRequest;
use App\Modules\Admin\Http\Requests\Hospital\HospitalListForStaffRequest;
use App\Modules\Admin\Http\Requests\Hospital\HospitalUpdateForStaffRequest;
use App\Modules\Admin\Http\Requests\Hospital\UpdateHospitalRequest;
use Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class HospitalController extends Controller
{
    /**
     * GET /admin/hospitals
     * Inertia 페이지 렌더 (데이터는 /admin/api/hospitals 에서 로드)
     * (Beaulab)뷰랩 전용 병원 리스트 페이지
     */
    public function indexHospitalPageForStaff(): InertiaResponse
    {
        return Inertia::render('admin/hospitals/index-hospitals');
    }

    /**
     * GET /admin/hospitals/create
     * Inertia 페이지 렌더
     * (Beaulab)뷰랩 전용 병원 생성 페이지
     */
    public function createHospitalForStaff(): InertiaResponse
    {
        return Inertia::render('admin/hospitals/create-hospital');
    }

    /**
     * GET /admin/hospitals/create
     * Inertia 페이지 렌더
     * (Beaulab)뷰랩 전용 병원 생성 페이지
     */
    public function updateHospitalForStaff(): InertiaResponse
    {
        return Inertia::render('admin/hospitals/update-hospital');
    }

    /**
     * GET /admin/api/hospitals
     * (Beaulab)뷰랩 전용 병원 리스트 api
     */
    public function apiGetHospitalListForStaff(
        HospitalListForStaffRequest $request,
        HospitalListForStaffAction $action,
    ) {
        $data = $action->execute($request->filters());
        return ApiResponse::success($data['items'], $data['meta']);
    }

    /**
     * POST /admin/api/hospitals/create
     * (Beaulab)뷰랩 전용 병원 생성 api
     */
    public function apiCreateHospitalForStaff(
        HospitalCreateForStaffRequest $request,
        HospitalCreateForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['hospital']);
    }

    /**
     * PUT|PATCH /admin/api/hospitals/{hospital}
     * (Beaulab) 뷰랩 직원 전용 병원 수정 API
     */
    public function apiUpdateHospitalForStaff(
        Hospital $hospital,
        HospitalUpdateForStaffRequest $request,
        HospitalUpdateForStaffAction $action,
    ) {
        $result = $action->execute($hospital, $request->filters());

        return ApiResponse::success($result['hospital']);
    }

    /**
     * DELETE /admin/api/hospitals/{hospital}
     * (Beaulab) 뷰랩 직원 전용 병원 삭제
     */
    public function apiDeleteHospitalForStaff(
        Hospital $hospital,
        HospitalDeleteForStaffAction $action,
    ) {
        $action->execute($hospital);

        return ApiResponse::success();
    }

    /**
     * 위 뷰랩 전용
     *
    -------------------------------------------------------------------------------------------------------
     *
     * 아래 병원회원 전용
     **/

    /**
     * GET /admin/hospital
     * (Hospital)병원 전용 내 병원 페이지
     */
    public function myHospitalForHospital(): InertiaResponse
    {
        return Inertia::render('admin/hospitals/my-hospital');
    }

    /**
     * GET /admin/hospital
     * (Hospital)병원 전용 내 병원 정보 api
     */
    public function apiGetMyHospitalForHospital()
    {
        $admin = Auth::guard('admin')->user();

        // activeMembership 기반으로 내 병원 조회
        $membership = $admin?->activeMembership;

        if (! $membership || $membership->type !== 'hospital') {
            return ApiResponse::error('FORBIDDEN', '병원 계정이 아닙니다.', null, null, 403);
        }

        $hospitalId = (int) $membership->target_id;

        $hospital = Hospital::query()->findOrFail($hospitalId);

        // 단일 조회 스코프 체크 (자기 병원만)
        $this->authorize('view', $hospital);

        return ApiResponse::success([
            'hospital' => $hospital,
        ]);
    }

    /**
     * GET /admin/hospital
     * (Hospital)병원 전용 내 병원 정보 수정 api
     */
    public function apiUpdateMyHospitalForHospital()
    {

    }
}
