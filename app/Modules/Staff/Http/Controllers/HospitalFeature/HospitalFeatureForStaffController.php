<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalFeature;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalFeature\Actions\Staff\HospitalFeatureListForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalFeature\HospitalFeatureListForStaffRequest;

final class HospitalFeatureForStaffController extends Controller
{
    public function getHospitalFeaturesForStaff(
        HospitalFeatureListForStaffRequest $request,
        HospitalFeatureListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }
}
