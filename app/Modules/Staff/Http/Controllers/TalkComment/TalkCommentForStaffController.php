<?php

namespace App\Modules\Staff\Http\Controllers\TalkComment;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Talk\Actions\Staff\TalkCommentCreateForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkCommentDeleteForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkCommentGetForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkCommentListForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkCommentUpdateForStaffAction;
use App\Domains\Talk\Models\TalkComment;
use App\Modules\Staff\Http\Requests\TalkComment\TalkCommentCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\TalkComment\TalkCommentGetForStaffRequest;
use App\Modules\Staff\Http\Requests\TalkComment\TalkCommentListForStaffRequest;
use App\Modules\Staff\Http\Requests\TalkComment\TalkCommentUpdateForStaffRequest;

final class TalkCommentForStaffController extends Controller
{
    public function getCommentsForStaff(TalkCommentListForStaffRequest $request, TalkCommentListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getCommentForStaff(TalkComment $comment, TalkCommentGetForStaffRequest $request, TalkCommentGetForStaffAction $action)
    {
        $result = $action->execute($comment, $request->filters());

        return ApiResponse::success($result['comment'] ?? $result);
    }

    public function storeCommentForStaff(TalkCommentCreateForStaffRequest $request, TalkCommentCreateForStaffAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['comment'] ?? $result);
    }

    public function updateCommentForStaff(TalkComment $comment, TalkCommentUpdateForStaffRequest $request, TalkCommentUpdateForStaffAction $action)
    {
        $result = $action->execute($comment, $request->validated());

        return ApiResponse::success($result['comment'] ?? $result);
    }

    public function deleteCommentForStaff(TalkComment $comment, TalkCommentDeleteForStaffAction $action)
    {
        $result = $action->execute($comment);

        return ApiResponse::success($result);
    }
}
