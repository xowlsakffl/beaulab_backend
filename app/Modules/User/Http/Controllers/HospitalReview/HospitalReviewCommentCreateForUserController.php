<?php

namespace App\Modules\User\Http\Controllers\HospitalReview;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalReview\Actions\User\HospitalReviewCommentCreateForUserAction;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Modules\User\Http\Requests\HospitalReview\HospitalReviewCommentCreateForUserRequest;

final class HospitalReviewCommentCreateForUserController extends Controller
{
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
}
