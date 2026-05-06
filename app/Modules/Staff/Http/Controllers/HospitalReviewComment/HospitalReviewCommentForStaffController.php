<?php

namespace App\Modules\Staff\Http\Controllers\HospitalReviewComment;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalReview\Actions\Staff\HospitalReviewCommentListForStaffAction;
use App\Domains\HospitalReview\Actions\Staff\HospitalReviewCommentStatusUpdateForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalReviewComment\HospitalReviewCommentListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalReviewComment\HospitalReviewCommentStatusUpdateForStaffRequest;

final class HospitalReviewCommentForStaffController extends Controller
{
    public function getCommentsForStaff(
        HospitalReviewCommentListForStaffRequest $request,
        HospitalReviewCommentListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function updateHospitalReviewCommentStatusForStaff(
        HospitalReviewCommentStatusUpdateForStaffRequest $request,
        HospitalReviewCommentStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }
}
