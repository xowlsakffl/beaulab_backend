<?php

namespace App\Modules\Staff\Http\Controllers\AdminNote;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\AdminNote\Actions\AdminNoteCreateAction;
use App\Domains\Common\AdminNote\Actions\AdminNoteListAction;
use App\Domains\Common\AdminNote\Actions\AdminNoteUpdateAction;
use App\Domains\Common\AdminNote\Models\AdminNote;
use App\Modules\Staff\Http\Requests\AdminNote\AdminNoteListForStaffRequest;
use App\Modules\Staff\Http\Requests\AdminNote\AdminNoteCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\AdminNote\AdminNoteUpdateForStaffRequest;

/**
 * AdminNoteForStaffController 역할 정의.
 * 스태프 모듈의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class AdminNoteForStaffController extends Controller
{
    public function getAdminNotesForStaff(
        AdminNoteListForStaffRequest $request,
        AdminNoteListAction $action
    ) {
        $result = $action->execute($request->user(), $request->filters());

        return ApiResponse::success($result['items'] ?? $result);
    }

    public function createAdminNoteForStaff(
        AdminNoteCreateForStaffRequest $request,
        AdminNoteCreateAction          $action
    ) {
        $result = $action->execute($request->user(), $request->validated());

        return ApiResponse::success($result['note'] ?? $result);
    }

    public function updateAdminNoteForStaff(
        AdminNote $note,
        AdminNoteUpdateForStaffRequest $request,
        AdminNoteUpdateAction $action
    ) {
        $result = $action->execute($request->user(), $note, $request->validated());

        return ApiResponse::success($result['note'] ?? $result);
    }
}
