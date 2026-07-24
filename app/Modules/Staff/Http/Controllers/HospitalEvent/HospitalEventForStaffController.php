<?php

namespace App\Modules\Staff\Http\Controllers\HospitalEvent;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventAdminStatusUpdateForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventAllowStatusUpdateForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventCategoryFilterOptionsForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventCreateForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventDeleteForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventDuplicateForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventGetForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventListForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventOperationHistoriesForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventPeriodUpdateForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventSummaryForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventUpdateForStaffAction;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventAdminStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventAllowStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventDuplicateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventGetForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventPeriodUpdateForStaffRequest;
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

    public function getHospitalEventSummaryForStaff(HospitalEventSummaryForStaffAction $action)
    {
        return ApiResponse::success($action->execute());
    }

    public function getHospitalEventCategoryFilterOptionsForStaff(HospitalEventCategoryFilterOptionsForStaffAction $action)
    {
        return ApiResponse::success($action->execute());
    }

    public function getHospitalEventForStaff(HospitalEvent $hospitalEvent, HospitalEventGetForStaffAction $action)
    {
        $result = $action->execute($hospitalEvent);

        return ApiResponse::success($result['event']);
    }

    public function createHospitalEventForStaff(
        HospitalEventCreateForStaffRequest $request,
        HospitalEventCreateForStaffAction $action,
    ) {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['event']);
    }

    public function duplicateHospitalEventForStaff(
        HospitalEvent $hospitalEvent,
        HospitalEventDuplicateForStaffRequest $request,
        HospitalEventDuplicateForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEvent, $request->validated());

        return ApiResponse::success($result['event']);
    }

    public function updateHospitalEventForStaff(
        HospitalEvent $hospitalEvent,
        HospitalEventUpdateForStaffRequest $request,
        HospitalEventUpdateForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEvent, $request->validated());

        return ApiResponse::success($result['event']);
    }

    public function updateHospitalEventPeriodForStaff(
        HospitalEvent $hospitalEvent,
        HospitalEventPeriodUpdateForStaffRequest $request,
        HospitalEventPeriodUpdateForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEvent, $request->validated());

        return ApiResponse::success($result['event']);
    }

    public function deleteHospitalEventForStaff(HospitalEvent $hospitalEvent, HospitalEventDeleteForStaffAction $action)
    {
        return ApiResponse::success($action->execute($hospitalEvent));
    }

    public function updateHospitalEventAdminStatusForStaff(
        HospitalEventAdminStatusUpdateForStaffRequest $request,
        HospitalEventAdminStatusUpdateForStaffAction $action,
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
