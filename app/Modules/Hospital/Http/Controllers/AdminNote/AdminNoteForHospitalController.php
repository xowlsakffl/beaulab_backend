<?php

namespace App\Modules\Hospital\Http\Controllers\AdminNote;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Actions\AdminNote\AdminNoteListAction;
use App\Domains\Common\Actions\AdminNote\AdminNoteStoreAction;
use App\Domains\Common\Actions\AdminNote\AdminNoteUpdateAction;
use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Modules\Hospital\Http\Requests\AdminNote\AdminNoteListForHospitalRequest;
use App\Modules\Hospital\Http\Requests\AdminNote\AdminNoteStoreForHospitalRequest;
use App\Modules\Hospital\Http\Requests\AdminNote\AdminNoteUpdateForHospitalRequest;

final class AdminNoteForHospitalController extends Controller
{
    public function getAdminNotesForHospital(
        AdminNoteListForHospitalRequest $request,
        AdminNoteListAction $action
    ) {
        $result = $action->execute($request->user(), $request->filters());

        return ApiResponse::success($result['items'] ?? $result);
    }

    public function storeAdminNoteForHospital(
        AdminNoteStoreForHospitalRequest $request,
        AdminNoteStoreAction $action
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
