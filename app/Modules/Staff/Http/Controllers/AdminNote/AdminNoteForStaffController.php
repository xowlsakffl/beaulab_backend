<?php

namespace App\Modules\Staff\Http\Controllers\AdminNote;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Actions\AdminNote\AdminNoteListAction;
use App\Domains\Common\Actions\AdminNote\AdminNoteStoreAction;
use App\Domains\Common\Actions\AdminNote\AdminNoteUpdateAction;
use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Modules\Staff\Http\Requests\AdminNote\AdminNoteListForStaffRequest;
use App\Modules\Staff\Http\Requests\AdminNote\AdminNoteStoreForStaffRequest;
use App\Modules\Staff\Http\Requests\AdminNote\AdminNoteUpdateForStaffRequest;

final class AdminNoteForStaffController extends Controller
{
    public function getAdminNotesForStaff(
        AdminNoteListForStaffRequest $request,
        AdminNoteListAction $action
    ) {
        $result = $action->execute($request->user(), $request->filters());

        return ApiResponse::success($result['items'] ?? $result);
    }

    public function storeAdminNoteForStaff(
        AdminNoteStoreForStaffRequest $request,
        AdminNoteStoreAction $action
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
