<?php

namespace App\Modules\Hospital\Http\Controllers\AdminNote;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\AdminNote\Actions\AdminNoteCreateAction;
use App\Domains\Common\AdminNote\Actions\AdminNoteListAction;
use App\Domains\Common\AdminNote\Actions\AdminNoteUpdateAction;
use App\Domains\Common\AdminNote\Models\AdminNote;
use App\Modules\Hospital\Http\Requests\AdminNote\AdminNoteListForHospitalRequest;
use App\Modules\Hospital\Http\Requests\AdminNote\AdminNoteCreateForHospitalRequest;
use App\Modules\Hospital\Http\Requests\AdminNote\AdminNoteUpdateForHospitalRequest;

/**
 * AdminNoteForHospitalController 역할 정의.
 * 병원 도메인의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class AdminNoteForHospitalController extends Controller
{
    public function getAdminNotesForHospital(
        AdminNoteListForHospitalRequest $request,
        AdminNoteListAction $action
    ) {
        $result = $action->execute($request->user(), $request->filters());

        return ApiResponse::success($result['items'] ?? $result);
    }

    public function createAdminNoteForHospital(
        AdminNoteCreateForHospitalRequest $request,
        AdminNoteCreateAction             $action
    ) {
        $result = $action->execute($request->user(), $request->validated());

        return ApiResponse::success($result['note'] ?? $result);
    }

    public function updateAdminNoteForHospital(
        AdminNote $note,
        AdminNoteUpdateForHospitalRequest $request,
        AdminNoteUpdateAction $action
    ) {
        $result = $action->execute($request->user(), $note, $request->validated());

        return ApiResponse::success($result['note'] ?? $result);
    }
}
