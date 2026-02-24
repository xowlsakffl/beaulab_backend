<?php

namespace App\Modules\Staff\Http\Controllers\Doctor;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Doctor\Actions\Staff\DoctorCreateForStaffAction;
use App\Domains\Doctor\Actions\Staff\DoctorDeleteForStaffAction;
use App\Domains\Doctor\Actions\Staff\DoctorGetForStaffAction;
use App\Domains\Doctor\Actions\Staff\DoctorListForStaffAction;
use App\Domains\Doctor\Actions\Staff\DoctorUpdateForStaffAction;
use App\Domains\Doctor\Models\Doctor;
use App\Modules\Staff\Http\Requests\Doctor\DoctorCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Doctor\DoctorListForStaffRequest;
use App\Modules\Staff\Http\Requests\Doctor\DoctorUpdateForStaffRequest;

final class DoctorForStaffController extends Controller
{
    public function getDoctorsForStaff(DoctorListForStaffRequest $request, DoctorListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getDoctorForStaff(Doctor $doctor, DoctorGetForStaffAction $action)
    {
        $result = $action->execute($doctor);

        return ApiResponse::success($result['doctor'] ?? $result);
    }

    public function storeDoctorForStaff(DoctorCreateForStaffRequest $request, DoctorCreateForStaffAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['doctor'] ?? $result);
    }

    public function updateDoctorForStaff(Doctor $doctor, DoctorUpdateForStaffRequest $request, DoctorUpdateForStaffAction $action)
    {
        $result = $action->execute($doctor, $request->validated());

        return ApiResponse::success($result['doctor'] ?? $result);
    }

    public function deleteDoctorForStaff(Doctor $doctor, DoctorDeleteForStaffAction $action)
    {
        $result = $action->execute($doctor);

        return ApiResponse::success($result);
    }
}
