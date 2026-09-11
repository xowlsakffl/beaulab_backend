<?php

namespace App\Modules\Staff\Http\Controllers\HospitalPromotion;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalPromotion\Actions\Common\HospitalPromotionEditorImagesAction;
use App\Domains\HospitalPromotion\Actions\Staff\HospitalPromotionReadForStaffAction;
use App\Domains\HospitalPromotion\Actions\Staff\HospitalPromotionSaveForStaffAction;
use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use App\Modules\Staff\Http\Requests\HospitalPromotion\HospitalPromotionAvailabilityForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalPromotion\HospitalPromotionEditorImageCleanupForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalPromotion\HospitalPromotionEditorImageUploadForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalPromotion\HospitalPromotionHistoryForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalPromotion\HospitalPromotionListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalPromotion\HospitalPromotionSaveForStaffRequest;

final class HospitalPromotionForStaffController extends Controller
{
    public function board(HospitalPromotionListForStaffRequest $request, HospitalPromotionReadForStaffAction $action)
    {
        return ApiResponse::success($action->board($request->validated()));
    }

    public function index(HospitalPromotionListForStaffRequest $request, HospitalPromotionReadForStaffAction $action)
    {
        $result = $action->ended($request->validated());

        return ApiResponse::success($result['items'], $result['meta']);
    }

    public function show(HospitalPromotion $promotion, HospitalPromotionReadForStaffAction $action)
    {
        return ApiResponse::success($action->detail($promotion));
    }

    public function store(HospitalPromotionSaveForStaffRequest $request, HospitalPromotionSaveForStaffAction $action)
    {
        return ApiResponse::success($action->execute($request->validated()));
    }

    public function update(HospitalPromotion $promotion, HospitalPromotionSaveForStaffRequest $request, HospitalPromotionSaveForStaffAction $action)
    {
        return ApiResponse::success($action->execute($request->validated(), $promotion));
    }

    public function histories(HospitalPromotion $promotion, HospitalPromotionHistoryForStaffRequest $request, HospitalPromotionReadForStaffAction $action)
    {
        $result = $action->histories($promotion, $request->validated());

        return ApiResponse::success($result['items'], $result['meta']);
    }

    public function availability(HospitalPromotionAvailabilityForStaffRequest $request, HospitalPromotionReadForStaffAction $action)
    {
        return ApiResponse::success($action->availability($request->validated()));
    }

    public function uploadEditorImage(HospitalPromotionEditorImageUploadForStaffRequest $request, HospitalPromotionEditorImagesAction $action)
    {
        $payload = $request->validated();

        return ApiResponse::success($action->upload($request->file('image'), isset($payload['promotion_id']) ? (int) $payload['promotion_id'] : null));
    }

    public function cleanupEditorImages(HospitalPromotionEditorImageCleanupForStaffRequest $request, HospitalPromotionEditorImagesAction $action)
    {
        $payload = $request->validated();

        return ApiResponse::success($action->cleanup($payload['paths'] ?? [], $payload['urls'] ?? []));
    }
}
