<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalEntry;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEntry\Actions\Staff\HospitalEntryAllowStatusUpdateForStaffAction;
use App\Domains\HospitalEntry\Actions\Staff\HospitalEntryGetForStaffAction;
use App\Domains\HospitalEntry\Actions\Staff\HospitalEntryListForStaffAction;
use App\Domains\HospitalEntry\Actions\Staff\HospitalEntrySummaryForStaffAction;
use App\Domains\HospitalEntry\Actions\Staff\HospitalEntryUpdateForStaffAction;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use App\Modules\Staff\Http\Requests\HospitalEntry\HospitalEntryAllowStatusUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEntry\HospitalEntryListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalEntry\HospitalEntryUpdateForStaffRequest;

final class HospitalEntryForStaffController extends Controller
{
    public function getHospitalEntriesForStaff(
        HospitalEntryListForStaffRequest $request,
        HospitalEntryListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getHospitalEntrySummaryForStaff(HospitalEntrySummaryForStaffAction $action)
    {
        return ApiResponse::success($action->execute());
    }

    public function updateHospitalEntryAllowStatusForStaff(
        HospitalEntryAllowStatusUpdateForStaffRequest $request,
        HospitalEntryAllowStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function getHospitalEntryForStaff(
        HospitalEntry $hospitalEntry,
        HospitalEntryGetForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEntry);

        return ApiResponse::success($result['hospital_entry']);
    }

    public function updateHospitalEntryForStaff(
        HospitalEntry $hospitalEntry,
        HospitalEntryUpdateForStaffRequest $request,
        HospitalEntryUpdateForStaffAction $action,
    ) {
        $result = $action->execute($hospitalEntry, $request->validated());

        return ApiResponse::success($result['hospital_entry']);
    }
}
