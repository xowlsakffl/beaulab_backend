<?php

namespace App\Modules\Staff\Http\Controllers\Talk;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Talk\Actions\Staff\TalkCommentsForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkExcelDownloadForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkGetForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkListForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkOperationHistoriesForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkStatusUpdateForStaffAction;
use App\Domains\Talk\Models\Talk;
use App\Modules\Staff\Http\Requests\Talk\TalkExcelDownloadForStaffRequest;
use App\Modules\Staff\Http\Requests\Talk\TalkGetForStaffRequest;
use App\Modules\Staff\Http\Requests\Talk\TalkListForStaffRequest;
use App\Modules\Staff\Http\Requests\Talk\TalkStatusUpdateForStaffRequest;

/**
 * TalkForStaffController 역할 정의.
 * 토크 도메인의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class TalkForStaffController extends Controller
{
    public function getTalksForStaff(TalkListForStaffRequest $request, TalkListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function downloadTalksExcelForStaff(TalkExcelDownloadForStaffRequest $request, TalkExcelDownloadForStaffAction $action)
    {
        return $action->execute($request->filters());
    }

    public function getTalkForStaff(Talk $talk, TalkGetForStaffRequest $request, TalkGetForStaffAction $action)
    {
        $result = $action->execute($talk, $request->filters());

        return ApiResponse::success($result['talk']);
    }

    public function getTalkCommentsForStaff(
        Talk $talk,
        TalkGetForStaffRequest $request,
        TalkCommentsForStaffAction $action,
    ) {
        $result = $action->execute($talk, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getTalkOperationHistoriesForStaff(
        Talk $talk,
        TalkGetForStaffRequest $request,
        TalkOperationHistoriesForStaffAction $action,
    ) {
        $result = $action->execute($talk, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function updateTalkStatusForStaff(
        TalkStatusUpdateForStaffRequest $request,
        TalkStatusUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }
}
