<?php

namespace App\Modules\Staff\Http\Controllers\BannerExposure;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\BannerExposure\Actions\BannerExposureReadAction;
use App\Domains\BannerExposure\Actions\BannerExposureSaveAction;
use App\Domains\BannerExposure\Models\BannerExposure;
use App\Modules\Staff\Http\Requests\BannerExposure\BannerExposureListRequest;
use App\Modules\Staff\Http\Requests\BannerExposure\BannerExposureMutationRequest;
use App\Modules\Staff\Http\Requests\BannerExposure\BannerExposureSaveRequest;
use App\Modules\Staff\Http\Requests\BannerExposure\BannerExposureTargetRequest;
use Illuminate\Http\Request;

final class BannerExposureForStaffController extends Controller
{
    public function board(BannerExposureListRequest $request, BannerExposureReadAction $action)
    {
        return ApiResponse::success($action->board($request->validated()));
    }

    public function targets(BannerExposureTargetRequest $request, BannerExposureReadAction $action)
    {
        return ApiResponse::success($action->targets($request->validated()));
    }

    public function show(BannerExposure $bannerExposure, BannerExposureReadAction $action)
    {
        return ApiResponse::success($action->detail($bannerExposure));
    }

    public function store(BannerExposureSaveRequest $request, BannerExposureSaveAction $action)
    {
        return ApiResponse::success($action->save($request->validated()));
    }

    public function update(BannerExposure $bannerExposure, BannerExposureSaveRequest $request, BannerExposureSaveAction $action)
    {
        return ApiResponse::success($action->save($request->validated(), $bannerExposure));
    }

    public function status(BannerExposure $bannerExposure, BannerExposureMutationRequest $request, BannerExposureSaveAction $action)
    {
        return ApiResponse::success($action->save($request->validated(), $bannerExposure));
    }

    public function destroy(BannerExposure $bannerExposure, BannerExposureMutationRequest $request, BannerExposureSaveAction $action)
    {
        $action->delete($bannerExposure, $request->validated());

        return ApiResponse::success();
    }

    public function reorder(BannerExposureMutationRequest $request, BannerExposureSaveAction $action)
    {
        $action->reorder($request->validated());

        return ApiResponse::success();
    }

    public function histories(BannerExposure $bannerExposure, Request $request, BannerExposureReadAction $action)
    {
        $filters = $request->validate(['operation_histories_page' => ['sometimes', 'integer', 'min:1']]);
        $result = $action->histories($bannerExposure, $filters);

        return ApiResponse::success($result['items'], $result['meta']);
    }
}
