<?php

namespace App\Modules\Staff\Http\Controllers\Faq;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Faq\Actions\Staff\FaqCreateForStaffAction;
use App\Domains\Faq\Actions\Staff\FaqDeleteForStaffAction;
use App\Domains\Faq\Actions\Staff\FaqEditorImageCleanupForStaffAction;
use App\Domains\Faq\Actions\Staff\FaqEditorImageUploadForStaffAction;
use App\Domains\Faq\Actions\Staff\FaqGetForStaffAction;
use App\Domains\Faq\Actions\Staff\FaqListForStaffAction;
use App\Domains\Faq\Actions\Staff\FaqUpdateForStaffAction;
use App\Domains\Faq\Models\Faq;
use App\Modules\Staff\Http\Requests\Faq\FaqCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Faq\FaqEditorImageCleanupForStaffRequest;
use App\Modules\Staff\Http\Requests\Faq\FaqEditorImageUploadForStaffRequest;
use App\Modules\Staff\Http\Requests\Faq\FaqGetForStaffRequest;
use App\Modules\Staff\Http\Requests\Faq\FaqListForStaffRequest;
use App\Modules\Staff\Http\Requests\Faq\FaqUpdateForStaffRequest;

final class FaqForStaffController extends Controller
{
    public function getFaqsForStaff(
        FaqListForStaffRequest $request,
        FaqListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getFaqForStaff(
        Faq $faq,
        FaqGetForStaffRequest $request,
        FaqGetForStaffAction $action,
    ) {
        $result = $action->execute($faq);

        return ApiResponse::success($result['faq'] ?? $result);
    }

    public function storeFaqForStaff(
        FaqCreateForStaffRequest $request,
        FaqCreateForStaffAction $action,
    ) {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['faq'] ?? $result);
    }

    public function uploadEditorImageForStaff(
        FaqEditorImageUploadForStaffRequest $request,
        FaqEditorImageUploadForStaffAction $action,
    ) {
        $validated = $request->validated();

        $result = $action->execute(
            $request->file('image'),
            isset($validated['faq_id']) ? (int) $validated['faq_id'] : null,
        );

        return ApiResponse::success($result);
    }

    public function cleanupEditorImagesForStaff(
        FaqEditorImageCleanupForStaffRequest $request,
        FaqEditorImageCleanupForStaffAction $action,
    ) {
        $validated = $request->validated();

        $result = $action->execute(
            $validated['paths'] ?? null,
            $validated['urls'] ?? null,
        );

        return ApiResponse::success($result);
    }

    public function updateFaqForStaff(
        Faq $faq,
        FaqUpdateForStaffRequest $request,
        FaqUpdateForStaffAction $action,
    ) {
        $result = $action->execute($faq, $request->validated());

        return ApiResponse::success($result['faq'] ?? $result);
    }

    public function deleteFaqForStaff(
        Faq $faq,
        FaqDeleteForStaffAction $action,
    ) {
        $result = $action->execute($faq);

        return ApiResponse::success($result);
    }
}
