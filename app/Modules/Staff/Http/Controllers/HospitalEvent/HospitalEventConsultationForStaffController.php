<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalEvent;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventConsultationAllowStatusUpdateForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventConsultationListForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventConsultationOperationHistoriesForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventConsultationStatusUpdateForStaffAction;
use App\Domains\HospitalEvent\Models\HospitalEventConsultation;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventConsultationAllowStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventConsultationListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventConsultationStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventGetForStaffRequest;

final class HospitalEventConsultationForStaffController extends Controller
{
    public function getHospitalEventConsultationsForStaff(
        HospitalEventConsultationListForStaffRequest $request,
        HospitalEventConsultationListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function updateHospitalEventConsultationStatusForStaff(
        HospitalEventConsultationStatusUpdateForStaffRequest $request,
        HospitalEventConsultationStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function updateHospitalEventConsultationAllowStatusForStaff(
        HospitalEventConsultationAllowStatusUpdateForStaffRequest $request,
        HospitalEventConsultationAllowStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function getHospitalEventConsultationOperationHistoriesForStaff(
        HospitalEventConsultation $hospitalEventConsultation,
        HospitalEventGetForStaffRequest $request,
        HospitalEventConsultationOperationHistoriesForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEventConsultation, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }
}
