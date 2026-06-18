<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalEvent;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventDBAllowStatusUpdateForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventDBListForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventDBOperationHistoriesForStaffAction;
use App\Domains\HospitalEvent\Actions\Staff\HospitalEventDBStatusUpdateForStaffAction;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventDBAllowStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventDBListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventDBStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEvent\HospitalEventGetForStaffRequest;

final class HospitalEventDBForStaffController extends Controller
{
    public function getHospitalEventDBsForStaff(
        HospitalEventDBListForStaffRequest $request,
        HospitalEventDBListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function updateHospitalEventDBStatusForStaff(
        HospitalEventDBStatusUpdateForStaffRequest $request,
        HospitalEventDBStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function updateHospitalEventDBAllowStatusForStaff(
        HospitalEventDBAllowStatusUpdateForStaffRequest $request,
        HospitalEventDBAllowStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function getHospitalEventDBOperationHistoriesForStaff(
        HospitalEventDB $hospitalEventDB,
        HospitalEventGetForStaffRequest $request,
        HospitalEventDBOperationHistoriesForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEventDB, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }
}
