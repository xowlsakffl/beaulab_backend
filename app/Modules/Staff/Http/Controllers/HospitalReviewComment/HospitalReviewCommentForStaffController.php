<?php

namespace App\Modules\Staff\Http\Controllers\HospitalReviewComment;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalReview\Actions\Staff\HospitalReviewCommentListForStaffAction;
use App\Domains\HospitalReview\Actions\Staff\HospitalReviewCommentOperationHistoriesForStaffAction;
use App\Domains\HospitalReview\Actions\Staff\HospitalReviewCommentStatusUpdateForStaffAction;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Modules\Staff\Http\Requests\HospitalReviewComment\HospitalReviewCommentListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalReviewComment\HospitalReviewCommentStatusUpdateForStaffRequest;
use Illuminate\Http\Request;

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

    public function getHospitalReviewCommentOperationHistoriesForStaff(
        HospitalReviewComment $comment,
        Request $request,
        HospitalReviewCommentOperationHistoriesForStaffAction $action,
    ) {
        $result = $action->execute($comment, $request->query());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }
}
