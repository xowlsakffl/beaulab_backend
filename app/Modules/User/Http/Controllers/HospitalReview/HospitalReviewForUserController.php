<?php

namespace App\Modules\User\Http\Controllers\HospitalReview;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalReview\Actions\User\HospitalReviewCommentCreateForUserAction;
use App\Domains\HospitalReview\Actions\User\HospitalReviewCommentDeleteForUserAction;
use App\Domains\HospitalReview\Actions\User\HospitalReviewCreateForUserAction;
use App\Domains\HospitalReview\Actions\User\HospitalReviewDeleteForUserAction;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Modules\User\Http\Requests\HospitalReview\HospitalReviewCommentCreateForUserRequest;
use App\Modules\User\Http\Requests\HospitalReview\HospitalReviewCreateForUserRequest;
use Illuminate\Http\Request;

final class HospitalReviewForUserController extends Controller
{
    public function createHospitalReviewForUser(HospitalReviewCreateForUserRequest $request, HospitalReviewCreateForUserAction $action)
    {
        $result = $action->execute($request->user(), $request->validated());

        return ApiResponse::success($result['hospital_review'] ?? $result);
    }

    public function deleteHospitalReviewForUser(
        Request $request,
        HospitalReview $hospitalReview,
        HospitalReviewDeleteForUserAction $action,
    ) {
        $result = $action->execute($request->user(), $hospitalReview);

        return ApiResponse::success($result['hospital_review'] ?? $result);
    }

    public function createHospitalReviewCommentForUser(
        HospitalReview $hospitalReview,
        HospitalReviewCommentCreateForUserRequest $request,
        HospitalReviewCommentCreateForUserAction $action,
    ) {
        $result = $action->execute($request->user(), $hospitalReview, [
            ...$request->validated(),
            'author_ip' => $request->ip(),
        ]);

        return ApiResponse::success($result['comment'] ?? $result);
    }

    public function deleteHospitalReviewCommentForUser(
        Request $request,
        HospitalReview $hospitalReview,
        HospitalReviewComment $comment,
        HospitalReviewCommentDeleteForUserAction $action,
    ) {
        $result = $action->execute($request->user(), $hospitalReview, $comment);

        return ApiResponse::success($result['comment'] ?? $result);
    }
}
