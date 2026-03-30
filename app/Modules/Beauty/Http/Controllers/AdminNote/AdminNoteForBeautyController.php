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
