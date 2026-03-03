<?php

namespace App\Modules\Hospital\Http\Controllers\VideoRequest;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\VideoRequest\Actions\Hospital\VideoRequestCreateForHospitalAction;
use App\Domains\VideoRequest\Actions\Hospital\VideoRequestCancelForHospitalAction;
use App\Domains\VideoRequest\Actions\Hospital\VideoRequestGetForHospitalAction;
use App\Domains\VideoRequest\Actions\Hospital\VideoRequestListForHospitalAction;
use App\Domains\VideoRequest\Actions\Hospital\VideoRequestUpdateForHospitalAction;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Modules\Hospital\Http\Requests\VideoRequest\VideoRequestCancelForHospitalRequest;
use App\Modules\Hospital\Http\Requests\VideoRequest\VideoRequestCreateForHospitalRequest;
use App\Modules\Hospital\Http\Requests\VideoRequest\VideoRequestListForHospitalRequest;
use App\Modules\Hospital\Http\Requests\VideoRequest\VideoRequestUpdateForHospitalRequest;

final class VideoRequestForHospitalController extends Controller
{
    public function getVideoRequestsForHospital(VideoRequestListForHospitalRequest $request, VideoRequestListForHospitalAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getVideoRequestForHospital(VideoRequest $videoRequest, VideoRequestGetForHospitalAction $action)
    {
        $result = $action->execute($videoRequest);

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function storeVideoRequestForHospital(VideoRequestCreateForHospitalRequest $request, VideoRequestCreateForHospitalAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function updateVideoRequestForHospital(VideoRequest $videoRequest, VideoRequestUpdateForHospitalRequest $request, VideoRequestUpdateForHospitalAction $action)
    {
        $result = $action->execute($videoRequest, $request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function cancelVideoRequestForHospital(VideoRequest $videoRequest, VideoRequestCancelForHospitalRequest $request, VideoRequestCancelForHospitalAction $action)
    {
        $result = $action->execute($videoRequest, $request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }
}
