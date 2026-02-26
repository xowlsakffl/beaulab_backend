<?php

namespace App\Modules\Partner\Http\Controllers\VideoRequest;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\VideoRequest\Actions\Partner\VideoRequestCreateForPartnerAction;
use App\Domains\VideoRequest\Actions\Partner\VideoRequestCancelForPartnerAction;
use App\Domains\VideoRequest\Actions\Partner\VideoRequestListForPartnerAction;
use App\Domains\VideoRequest\Actions\Partner\VideoRequestUpdateForPartnerAction;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Modules\Partner\Http\Requests\VideoRequest\VideoRequestCreateForPartnerRequest;
use App\Modules\Partner\Http\Requests\VideoRequest\VideoRequestListForPartnerRequest;
use App\Modules\Partner\Http\Requests\VideoRequest\VideoRequestUpdateForPartnerRequest;

final class VideoRequestForPartnerController extends Controller
{
    public function getVideoRequestsForPartner(VideoRequestListForPartnerRequest $request, VideoRequestListForPartnerAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function storeVideoRequestForPartner(VideoRequestCreateForPartnerRequest $request, VideoRequestCreateForPartnerAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function updateVideoRequestForPartner(VideoRequest $videoRequest, VideoRequestUpdateForPartnerRequest $request, VideoRequestUpdateForPartnerAction $action)
    {
        $result = $action->execute($videoRequest, $request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function cancelVideoRequestForPartner(VideoRequest $videoRequest, VideoRequestCancelForPartnerAction $action)
    {
        $result = $action->execute($videoRequest);

        return ApiResponse::success($result['video_request'] ?? $result);
    }
}
