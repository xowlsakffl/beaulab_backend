<?php

namespace App\Modules\Beauty\Http\Controllers\AdminNote;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Actions\AdminNote\AdminNoteListAction;
use App\Domains\Common\Actions\AdminNote\AdminNoteStoreAction;
use App\Domains\Common\Actions\AdminNote\AdminNoteUpdateAction;
use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Modules\Beauty\Http\Requests\AdminNote\AdminNoteListForBeautyRequest;
use App\Modules\Beauty\Http\Requests\AdminNote\AdminNoteStoreForBeautyRequest;
use App\Modules\Beauty\Http\Requests\AdminNote\AdminNoteUpdateForBeautyRequest;

/**
 * AdminNoteForBeautyController 역할 정의.
 * 뷰티 도메인의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class AdminNoteForBeautyController extends Controller
{
    public function getAdminNotesForBeauty(
        AdminNoteListForBeautyRequest $request,
        AdminNoteListAction $action
    ) {
        $result = $action->execute($request->user(), $request->filters());

        return ApiResponse::success($result['items'] ?? $result);
    }

    public function storeAdminNoteForBeauty(
        AdminNoteStoreForBeautyRequest $request,
        AdminNoteStoreAction $action
    ) {
        $result = $action->execute($request->user(), $request->validated());

        return ApiResponse::success($result['note'] ?? $result);
    }

    public function updateAdminNoteForBeauty(
        AdminNote $note,
        AdminNoteUpdateForBeautyRequest $request,
        AdminNoteUpdateAction $action
    ) {
        $result = $action->execute($request->user(), $note, $request->validated());

        return ApiResponse::success($result['note'] ?? $result);
    }
}
