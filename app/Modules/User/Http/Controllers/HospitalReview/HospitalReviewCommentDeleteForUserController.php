<?php

namespace App\Modules\User\Http\Controllers\HospitalReview;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalReview\Actions\User\HospitalReviewCommentDeleteForUserAction;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use Illuminate\Http\Request;

final class HospitalReviewCommentDeleteForUserController extends Controller
{
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
