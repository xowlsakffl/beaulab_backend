<?php

declare(strict_types=1);

namespace App\Modules\Partner\Http\Controllers\Hospital;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Hospital\Models\Hospital;
use Auth;

final class HospitalForPartnerController extends Controller
{
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
