<?php

namespace App\Modules\Staff\Http\Controllers\HospitalEventAd;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEventAd\Actions\Staff\HospitalEventAdAllowStatusUpdateForStaffAction;
use App\Domains\HospitalEventAd\Actions\Staff\HospitalEventAdAvailabilityForStaffAction;
use App\Domains\HospitalEventAd\Actions\Staff\HospitalEventAdCalendarForStaffAction;
use App\Domains\HospitalEventAd\Actions\Staff\HospitalEventAdCreateForStaffAction;
use App\Domains\HospitalEventAd\Actions\Staff\HospitalEventAdGetForStaffAction;
use App\Domains\HospitalEventAd\Actions\Staff\HospitalEventAdListForStaffAction;
use App\Domains\HospitalEventAd\Actions\Staff\HospitalEventAdOperationHistoriesForStaffAction;
use App\Domains\HospitalEventAd\Actions\Staff\HospitalEventAdPlacementOptionListForStaffAction;
use App\Domains\HospitalEventAd\Actions\Staff\HospitalEventAdUpdateForStaffAction;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Modules\Staff\Http\Requests\HospitalEventAd\HospitalEventAdAllowStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEventAd\HospitalEventAdAvailabilityForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEventAd\HospitalEventAdCalendarForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEventAd\HospitalEventAdCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEventAd\HospitalEventAdGetForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEventAd\HospitalEventAdListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEventAd\HospitalEventAdUpdateForStaffRequest;

final class HospitalEventAdForStaffController extends Controller
{
    public function getHospitalEventAdPlacementOptionsForStaff(
        HospitalEventAdPlacementOptionListForStaffAction $action,
    ) {
        $result = $action->execute();

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getHospitalEventAdAvailabilityForStaff(
        HospitalEventAdAvailabilityForStaffRequest $request,
        HospitalEventAdAvailabilityForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->filters()));
    }

    public function getHospitalEventAdCalendarForStaff(
        HospitalEventAdCalendarForStaffRequest $request,
        HospitalEventAdCalendarForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->filters()));
    }

    public function getHospitalEventAdsForStaff(
        HospitalEventAdListForStaffRequest $request,
        HospitalEventAdListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getHospitalEventAdForStaff(
        HospitalEventAd $hospitalEventAd,
        HospitalEventAdGetForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEventAd);

        return ApiResponse::success($result['hospital_event_ad']);
    }

    public function getHospitalEventAdOperationHistoriesForStaff(
        HospitalEventAd $hospitalEventAd,
        HospitalEventAdGetForStaffRequest $request,
        HospitalEventAdOperationHistoriesForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEventAd, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function createHospitalEventAdForStaff(
        HospitalEventAdCreateForStaffRequest $request,
        HospitalEventAdCreateForStaffAction $action,
    ) {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['hospital_event_ad']);
    }

    public function updateHospitalEventAdForStaff(
        HospitalEventAd $hospitalEventAd,
        HospitalEventAdUpdateForStaffRequest $request,
        HospitalEventAdUpdateForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEventAd, $request->validated());

        return ApiResponse::success($result['hospital_event_ad']);
    }

    public function updateHospitalEventAdAllowStatusForStaff(
        HospitalEventAdAllowStatusUpdateForStaffRequest $request,
        HospitalEventAdAllowStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }
}
