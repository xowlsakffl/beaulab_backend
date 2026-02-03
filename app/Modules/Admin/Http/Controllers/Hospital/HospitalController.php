<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers\Hospital;

use App\Common\Http\ApiResponse;
use App\Domains\Hospital\Actions\Admin\HospitalListForStaffAction;
use App\Domains\Hospital\Actions\Admin\UpdateHospital;
use App\Domains\Hospital\Dto\Admin\HospitalUpsert;
use App\Domains\Hospital\Models\Hospital;
use App\Modules\Admin\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\Hospital\HospitalListForStaffRequest;
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
     * GET /admin/api/hospitals
     * (Beaulab)뷰랩 전용 병원 리스트 api
     */
    public function apiGetHospitalListForStaff(
        HospitalListForStaffRequest $request,
        HospitalListForStaffAction $action,
    ) {
        $data = $action->execute($request->filters());
        return ApiResponse::success($data);
    }

    /**
     * GET /admin/api/hospitals
     * (Beaulab)뷰랩 전용 병원 수정 api
     */
    public function apiUpdateHospitalForStaff(UpdateHospitalRequest $request, Hospital $hospital, UpdateHospital $action)
    {
        $this->authorize('update', $hospital);

        // FormRequest에서 validate 끝낸 값만 DTO로 변환
        $dto = HospitalUpsert::fromArray($request->validated());

        $updated = $action->handle($hospital, $dto);

        return ApiResponse::success([
            'hospital' => $updated,
        ]);
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
