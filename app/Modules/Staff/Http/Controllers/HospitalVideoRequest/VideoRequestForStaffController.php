<?php

namespace App\Modules\Staff\Http\Controllers\HospitalVideoRequest;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\VideoRequest\Actions\Staff\HospitalVideoRequestDeleteForStaffAction;
use App\Domains\VideoRequest\Actions\Staff\HospitalVideoRequestGetForStaffAction;
use App\Domains\VideoRequest\Actions\Staff\HospitalVideoRequestListForStaffAction;
use App\Domains\VideoRequest\Actions\Staff\HospitalVideoRequestUpdateForStaffAction;
use App\Domains\VideoRequest\Models\HospitalVideoRequest;
use App\Modules\Staff\Http\Requests\VideoRequest\VideoRequestListForStaffRequest;
use App\Modules\Staff\Http\Requests\VideoRequest\VideoRequestUpdateForStaffRequest;

final class VideoRequestForStaffController extends Controller
{
    public function getVideoRequestsForStaff(VideoRequestListForStaffRequest $request, HospitalVideoRequestListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getVideoRequestForStaff(HospitalVideoRequest $videoRequest, HospitalVideoRequestGetForStaffAction $action)
    {
        $result = $action->execute($videoRequest);

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function updateVideoRequestForStaff(HospitalVideoRequest $videoRequest, VideoRequestUpdateForStaffRequest $request, HospitalVideoRequestUpdateForStaffAction $action)
    {
        $result = $action->execute($videoRequest, $request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function deleteVideoRequestForStaff(HospitalVideoRequest $videoRequest, HospitalVideoRequestDeleteForStaffAction $action)
    {
        $result = $action->execute($videoRequest);

        return ApiResponse::success($result);
    }
}
