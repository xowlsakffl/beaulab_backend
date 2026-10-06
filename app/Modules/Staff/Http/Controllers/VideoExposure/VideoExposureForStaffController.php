<?php

namespace App\Modules\Staff\Http\Controllers\VideoExposure;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\VideoExposure\Actions\VideoExposureReadAction;
use App\Domains\VideoExposure\Actions\VideoExposureSaveAction;
use App\Domains\VideoExposure\Dto\VideoExposureDto;
use App\Domains\VideoExposure\Models\VideoExposure;
use App\Modules\Staff\Http\Requests\VideoExposure\VideoExposureRequest;
use Illuminate\Support\Facades\Gate;

final class VideoExposureForStaffController extends Controller
{
    public function categories(VideoExposureReadAction $action)
    {
        return ApiResponse::success($action->categories());
    }

    public function board(VideoExposureRequest $request, VideoExposureReadAction $action)
    {
        return ApiResponse::success($action->board($request->validated()));
    }

    public function targets(VideoExposureRequest $request, VideoExposureReadAction $action)
    {
        return ApiResponse::success($action->targets($request->validated()));
    }

    public function show(VideoExposure $videoExposure)
    {
        Gate::authorize('view', $videoExposure);

        return ApiResponse::success(VideoExposureDto::detail($videoExposure));
    }

    public function store(VideoExposureRequest $request, VideoExposureSaveAction $action)
    {
        return ApiResponse::success($action->save($request->validated()));
    }

    public function update(VideoExposure $videoExposure, VideoExposureRequest $request, VideoExposureSaveAction $action)
    {
        return ApiResponse::success($action->save($request->validated(), $videoExposure));
    }

    public function order(VideoExposureRequest $request, VideoExposureSaveAction $action)
    {
        $action->reorder($request->validated());

        return ApiResponse::success();
    }
}
