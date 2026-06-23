<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalEntry;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalEntry\Actions\Staff\HospitalEntryListForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalEntry\HospitalEntryListForStaffRequest;

final class HospitalEntryForStaffController extends Controller
{
    public function getHospitalEntriesForStaff(
        HospitalEntryListForStaffRequest $request,
        HospitalEntryListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }
}
