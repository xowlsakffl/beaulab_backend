<?php

namespace App\Modules\Staff\Http\Controllers\ReviewExposure;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\ReviewExposure\Actions\ReviewExposureReadAction;
use App\Domains\ReviewExposure\Actions\ReviewExposureSaveAction;
use App\Domains\ReviewExposure\Dto\ReviewExposureDto;
use App\Domains\ReviewExposure\Models\ReviewExposure;
use App\Modules\Staff\Http\Requests\ReviewExposure\ReviewExposureRequest;
use Illuminate\Support\Facades\Gate;

final class ReviewExposureForStaffController extends Controller
{
    public function categories(ReviewExposureReadAction $action)
    {
        return ApiResponse::success($action->categories());
    }

    public function board(ReviewExposureRequest $request, ReviewExposureReadAction $action)
    {
        return ApiResponse::success($action->board($request->validated()));
    }

    public function targets(ReviewExposureRequest $request, ReviewExposureReadAction $action)
    {
        return ApiResponse::success($action->targets($request->validated()));
    }

    public function show(ReviewExposure $reviewExposure)
    {
        Gate::authorize('view', $reviewExposure);

        return ApiResponse::success(ReviewExposureDto::detail($reviewExposure));
    }

    public function store(ReviewExposureRequest $request, ReviewExposureSaveAction $action)
    {
        return ApiResponse::success($action->save($request->validated()));
    }

    public function update(ReviewExposure $reviewExposure, ReviewExposureRequest $request, ReviewExposureSaveAction $action)
    {
        return ApiResponse::success($action->save($request->validated(), $reviewExposure));
    }

    public function order(ReviewExposureRequest $request, ReviewExposureSaveAction $action)
    {
        $action->reorder($request->validated());

        return ApiResponse::success();
    }
}
