<?php

namespace App\Modules\Beauty\Http\Controllers\VideoRequest;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\VideoRequest\Actions\Beauty\VideoRequestCreateForBeautyAction;
use App\Domains\VideoRequest\Actions\Beauty\VideoRequestCancelForBeautyAction;
use App\Domains\VideoRequest\Actions\Beauty\VideoRequestGetForBeautyAction;
use App\Domains\VideoRequest\Actions\Beauty\VideoRequestListForBeautyAction;
use App\Domains\VideoRequest\Actions\Beauty\VideoRequestUpdateForBeautyAction;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Modules\Beauty\Http\Requests\VideoRequest\VideoRequestCancelForBeautyRequest;
use App\Modules\Beauty\Http\Requests\VideoRequest\VideoRequestCreateForBeautyRequest;
use App\Modules\Beauty\Http\Requests\VideoRequest\VideoRequestListForBeautyRequest;
use App\Modules\Beauty\Http\Requests\VideoRequest\VideoRequestUpdateForBeautyRequest;

final class VideoRequestForBeautyController extends Controller
{
    public function getVideoRequestsForBeauty(VideoRequestListForBeautyRequest $request, VideoRequestListForBeautyAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getVideoRequestForBeauty(VideoRequest $videoRequest, VideoRequestGetForBeautyAction $action)
    {
        $result = $action->execute($videoRequest);

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function storeVideoRequestForBeauty(VideoRequestCreateForBeautyRequest $request, VideoRequestCreateForBeautyAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function updateVideoRequestForBeauty(VideoRequest $videoRequest, VideoRequestUpdateForBeautyRequest $request, VideoRequestUpdateForBeautyAction $action)
    {
        $result = $action->execute($videoRequest, $request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function cancelVideoRequestForBeauty(VideoRequest $videoRequest, VideoRequestCancelForBeautyRequest $request, VideoRequestCancelForBeautyAction $action)
    {
        $result = $action->execute($videoRequest, $request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }
}
