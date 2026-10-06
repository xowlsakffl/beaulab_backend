<?php

namespace App\Modules\Staff\Http\Controllers\EventExposure;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\EventExposure\Actions\EventExposureReadAction;
use App\Domains\EventExposure\Actions\EventExposureSaveAction;
use App\Domains\EventExposure\Dto\EventExposureDto;
use App\Domains\EventExposure\Models\EventExposure;
use App\Modules\Staff\Http\Requests\EventExposure\EventExposureRequest;
use Illuminate\Support\Facades\Gate;

final class EventExposureForStaffController extends Controller
{
    public function categories(EventExposureReadAction $action)
    {
        return ApiResponse::success($action->categories());
    }

    public function board(EventExposureRequest $request, EventExposureReadAction $action)
    {
        return ApiResponse::success($action->board($request->validated()));
    }

    public function targets(EventExposureRequest $request, EventExposureReadAction $action)
    {
        return ApiResponse::success($action->targets($request->validated()));
    }

    public function show(EventExposure $eventExposure)
    {
        Gate::authorize('view', $eventExposure);

        return ApiResponse::success(EventExposureDto::detail($eventExposure));
    }

    public function store(EventExposureRequest $request, EventExposureSaveAction $action)
    {
        return ApiResponse::success($action->save($request->validated()));
    }

    public function update(EventExposure $eventExposure, EventExposureRequest $request, EventExposureSaveAction $action)
    {
        return ApiResponse::success($action->save($request->validated(), $eventExposure));
    }

    public function order(EventExposureRequest $request, EventExposureSaveAction $action)
    {
        $action->reorder($request->validated());

        return ApiResponse::success();
    }
}
