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

/**
 * TalkCommentForStaffController 역할 정의.
 * 스태프 모듈의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
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
