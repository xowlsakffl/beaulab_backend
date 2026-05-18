<?php

namespace App\Modules\Staff\Http\Controllers\TalkComment;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Talk\Actions\Staff\TalkCommentListForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkCommentOperationHistoriesForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkCommentStatusUpdateForStaffAction;
use App\Domains\Talk\Models\TalkComment;
use App\Modules\Staff\Http\Requests\TalkComment\TalkCommentListForStaffRequest;
use App\Modules\Staff\Http\Requests\TalkComment\TalkCommentStatusUpdateForStaffRequest;
use Illuminate\Http\Request;

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

    public function updateTalkCommentStatusForStaff(
        TalkCommentStatusUpdateForStaffRequest $request,
        TalkCommentStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function getTalkCommentOperationHistoriesForStaff(
        TalkComment $comment,
        Request $request,
        TalkCommentOperationHistoriesForStaffAction $action,
    ) {
        $result = $action->execute($comment, $request->query());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }
}
