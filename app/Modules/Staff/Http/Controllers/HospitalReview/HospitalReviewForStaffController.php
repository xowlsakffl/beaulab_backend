<?php

namespace App\Modules\Staff\Http\Controllers\HospitalReview;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalReview\Actions\Staff\HospitalReviewListForStaffAction;
use App\Domains\HospitalReview\Actions\Staff\HospitalReviewStatusUpdateForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalReview\HospitalReviewListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalReview\HospitalReviewStatusUpdateForStaffRequest;

final class HospitalReviewForStaffController extends Controller
{
    public function getHospitalReviewsForStaff(HospitalReviewListForStaffRequest $request, HospitalReviewListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function updateHospitalReviewStatusForStaff(
        HospitalReviewStatusUpdateForStaffRequest $request,
        HospitalReviewStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }
}
