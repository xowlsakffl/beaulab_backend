<?php

namespace App\Modules\Staff\Http\Controllers\HospitalDoctor;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalDoctor\Actions\Staff\DoctorHospitalOptionListForStaffAction;
use App\Domains\HospitalDoctor\Actions\Staff\HospitalDoctorCreateForStaffAction;
use App\Domains\HospitalDoctor\Actions\Staff\HospitalDoctorDeleteForStaffAction;
use App\Domains\HospitalDoctor\Actions\Staff\HospitalDoctorGetForStaffAction;
use App\Domains\HospitalDoctor\Actions\Staff\HospitalDoctorListForStaffAction;
use App\Domains\HospitalDoctor\Actions\Staff\HospitalDoctorUpdateForStaffAction;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Modules\Staff\Http\Requests\Doctor\DoctorCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Doctor\DoctorHospitalOptionListForStaffRequest;
use App\Modules\Staff\Http\Requests\Doctor\DoctorListForStaffRequest;
use App\Modules\Staff\Http\Requests\Doctor\DoctorUpdateForStaffRequest;

/**
 * DoctorForStaffController 역할 정의.
 * 병원 의사 도메인의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class DoctorForStaffController extends Controller
{
    public function getDoctorHospitalOptionsForStaff(
        DoctorHospitalOptionListForStaffRequest $request,
        DoctorHospitalOptionListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getDoctorsForStaff(DoctorListForStaffRequest $request, HospitalDoctorListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getDoctorForStaff(HospitalDoctor $doctor, HospitalDoctorGetForStaffAction $action)
    {
        $result = $action->execute($doctor);

        return ApiResponse::success($result['doctor'] ?? $result);
    }

    public function storeDoctorForStaff(DoctorCreateForStaffRequest $request, HospitalDoctorCreateForStaffAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['doctor'] ?? $result);
    }

    public function updateDoctorForStaff(HospitalDoctor $doctor, DoctorUpdateForStaffRequest $request, HospitalDoctorUpdateForStaffAction $action)
    {
        $result = $action->execute($doctor, $request->validated());

        return ApiResponse::success($result['doctor'] ?? $result);
    }

    public function deleteDoctorForStaff(HospitalDoctor $doctor, HospitalDoctorDeleteForStaffAction $action)
    {
        $result = $action->execute($doctor);

        return ApiResponse::success($result);
    }
}
