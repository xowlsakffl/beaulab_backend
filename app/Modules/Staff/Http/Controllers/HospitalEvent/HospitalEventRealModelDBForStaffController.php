<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalEvent;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventRealModelDBGetForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventRealModelDBListForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventRealModelDBOperationHistoriesForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventRealModelDBStatusUpdateForStaffAction;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventGetForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventRealModelDBListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventRealModelDBStatusUpdateForStaffRequest;

final class HospitalEventRealModelDBForStaffController extends Controller
{
    public function getHospitalEventRealModelDBsForStaff(
        HospitalEventRealModelDBListForStaffRequest $request,
        HospitalEventRealModelDBListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getHospitalEventRealModelDBForStaff(
        HospitalEventRealModelDB $hospitalEventRealModelDB,
        HospitalEventRealModelDBGetForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($hospitalEventRealModelDB));
    }

    public function updateHospitalEventRealModelDBStatusForStaff(
        HospitalEventRealModelDBStatusUpdateForStaffRequest $request,
        HospitalEventRealModelDBStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function getHospitalEventRealModelDBOperationHistoriesForStaff(
        HospitalEventRealModelDB $hospitalEventRealModelDB,
        HospitalEventGetForStaffRequest $request,
        HospitalEventRealModelDBOperationHistoriesForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEventRealModelDB, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }
}
