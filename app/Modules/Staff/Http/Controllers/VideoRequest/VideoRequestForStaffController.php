<?php

namespace App\Modules\Staff\Http\Controllers\VideoRequest;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\VideoRequest\Actions\Staff\VideoRequestDeleteForStaffAction;
use App\Domains\VideoRequest\Actions\Staff\VideoRequestGetForStaffAction;
use App\Domains\VideoRequest\Actions\Staff\VideoRequestListForStaffAction;
use App\Domains\VideoRequest\Actions\Staff\VideoRequestUpdateForStaffAction;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Modules\Staff\Http\Requests\VideoRequest\VideoRequestListForStaffRequest;
use App\Modules\Staff\Http\Requests\VideoRequest\VideoRequestUpdateForStaffRequest;

final class VideoRequestForStaffController extends Controller
{
    public function getVideoRequestsForStaff(VideoRequestListForStaffRequest $request, VideoRequestListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getVideoRequestForStaff(VideoRequest $videoRequest, VideoRequestGetForStaffAction $action)
    {
        $result = $action->execute($videoRequest);

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function updateVideoRequestForStaff(VideoRequest $videoRequest, VideoRequestUpdateForStaffRequest $request, VideoRequestUpdateForStaffAction $action)
    {
        $result = $action->execute($videoRequest, $request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function deleteVideoRequestForStaff(VideoRequest $videoRequest, VideoRequestDeleteForStaffAction $action)
    {
        $result = $action->execute($videoRequest);

        return ApiResponse::success($result);
    }
}
