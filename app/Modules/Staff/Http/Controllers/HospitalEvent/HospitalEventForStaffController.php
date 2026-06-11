<?php

namespace App\Modules\Staff\Http\Controllers\HospitalEvent;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventAllowStatusUpdateForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventCreateForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventDeleteForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventGetForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventListForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventOperationHistoriesForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventStatusUpdateForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventUpdateForStaffAction;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventAllowStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventGetForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventUpdateForStaffRequest;

final class HospitalEventForStaffController extends Controller
{
    public function getHospitalEventsForStaff(
        HospitalEventListForStaffRequest $request,
        HospitalEventListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getHospitalEventForStaff(HospitalEvent $hospitalEvent, HospitalEventGetForStaffAction $action)
    {
        $result = $action->execute($hospitalEvent);

        return ApiResponse::success($result['event'] ?? $result);
    }

    public function createHospitalEventForStaff(
        HospitalEventCreateForStaffRequest $request,
        HospitalEventCreateForStaffAction $action,
    ) {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['event'] ?? $result);
    }

    public function updateHospitalEventForStaff(
        HospitalEvent $hospitalEvent,
        HospitalEventUpdateForStaffRequest $request,
        HospitalEventUpdateForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEvent, $request->validated());

        return ApiResponse::success($result['event'] ?? $result);
    }

    public function deleteHospitalEventForStaff(HospitalEvent $hospitalEvent, HospitalEventDeleteForStaffAction $action)
    {
        return ApiResponse::success($action->execute($hospitalEvent));
    }

    public function updateHospitalEventStatusForStaff(
        HospitalEventStatusUpdateForStaffRequest $request,
        HospitalEventStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function updateHospitalEventAllowStatusForStaff(
        HospitalEventAllowStatusUpdateForStaffRequest $request,
        HospitalEventAllowStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function getHospitalEventOperationHistoriesForStaff(
        HospitalEvent $hospitalEvent,
        HospitalEventGetForStaffRequest $request,
        HospitalEventOperationHistoriesForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEvent, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }
}
