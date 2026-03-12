<?php

namespace App\Modules\Staff\Http\Controllers\Notice;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Notice\Actions\Staff\NoticeCreateForStaffAction;
use App\Domains\Notice\Actions\Staff\NoticeDeleteForStaffAction;
use App\Domains\Notice\Actions\Staff\NoticeEditorImageCleanupForStaffAction;
use App\Domains\Notice\Actions\Staff\NoticeEditorImageUploadForStaffAction;
use App\Domains\Notice\Actions\Staff\NoticeGetForStaffAction;
use App\Domains\Notice\Actions\Staff\NoticeListForStaffAction;
use App\Domains\Notice\Actions\Staff\NoticeSendPushForStaffAction;
use App\Domains\Notice\Actions\Staff\NoticeUpdateForStaffAction;
use App\Domains\Notice\Models\Notice;
use App\Modules\Staff\Http\Requests\Notice\NoticeCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Notice\NoticeEditorImageCleanupForStaffRequest;
use App\Modules\Staff\Http\Requests\Notice\NoticeEditorImageUploadForStaffRequest;
use App\Modules\Staff\Http\Requests\Notice\NoticeGetForStaffRequest;
use App\Modules\Staff\Http\Requests\Notice\NoticeListForStaffRequest;
use App\Modules\Staff\Http\Requests\Notice\NoticeUpdateForStaffRequest;

final class NoticeForStaffController extends Controller
{
    public function getNoticesForStaff(
        NoticeListForStaffRequest $request,
        NoticeListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getNoticeForStaff(
        Notice $notice,
        NoticeGetForStaffRequest $request,
        NoticeGetForStaffAction $action,
    ) {
        $result = $action->execute($notice);

        return ApiResponse::success($result['notice'] ?? $result);
    }

    public function storeNoticeForStaff(
        NoticeCreateForStaffRequest $request,
        NoticeCreateForStaffAction $action,
    ) {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['notice'] ?? $result);
    }

    public function uploadEditorImageForStaff(
        NoticeEditorImageUploadForStaffRequest $request,
        NoticeEditorImageUploadForStaffAction $action,
    ) {
        $validated = $request->validated();

        $result = $action->execute(
            $request->file('image'),
            isset($validated['notice_id']) ? (int) $validated['notice_id'] : null,
        );

        return ApiResponse::success($result);
    }

    public function cleanupEditorImagesForStaff(
        NoticeEditorImageCleanupForStaffRequest $request,
        NoticeEditorImageCleanupForStaffAction $action,
    ) {
        $validated = $request->validated();

        $result = $action->execute(
            $validated['paths'] ?? null,
            $validated['urls'] ?? null,
        );

        return ApiResponse::success($result);
    }

    public function updateNoticeForStaff(
        Notice $notice,
        NoticeUpdateForStaffRequest $request,
        NoticeUpdateForStaffAction $action,
    ) {
        $result = $action->execute($notice, $request->validated());

        return ApiResponse::success($result['notice'] ?? $result);
    }

    public function deleteNoticeForStaff(
        Notice $notice,
        NoticeDeleteForStaffAction $action,
    ) {
        $result = $action->execute($notice);

        return ApiResponse::success($result);
    }

    public function sendPushForStaff(
        Notice $notice,
        NoticeSendPushForStaffAction $action,
    ) {
        $result = $action->execute($notice);

        return ApiResponse::success($result);
    }
}
