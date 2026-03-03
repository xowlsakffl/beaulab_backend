<?php

namespace App\Modules\Hospital\Http\Controllers\VideoRequest;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalVideoRequest\Actions\Hospital\HospitalVideoRequestCancelForHospitalAction;
use App\Domains\HospitalVideoRequest\Actions\Hospital\HospitalVideoRequestGetForHospitalAction;
use App\Domains\HospitalVideoRequest\Actions\Hospital\HospitalVideoRequestListForHospitalAction;
use App\Domains\HospitalVideoRequest\Actions\Hospital\HospitalVideoRequestUpdateForHospitalAction;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;
use App\Domains\HospitalVideoRequest\Actions\Hospital\HospitalVideoRequestCreateForHospitalAction;
use App\Modules\Hospital\Http\Requests\VideoRequest\HospitalVideoRequestCancelForHospitalRequest;
use App\Modules\Hospital\Http\Requests\VideoRequest\HospitalVideoRequestCreateForHospitalRequest;
use App\Modules\Hospital\Http\Requests\VideoRequest\HospitalVideoRequestListForHospitalRequest;
use App\Modules\Hospital\Http\Requests\VideoRequest\HospitalVideoRequestUpdateForHospitalRequest;

final class VideoRequestForHospitalController extends Controller
{
    public function getVideoRequestsForHospital(HospitalVideoRequestListForHospitalRequest $request, HospitalVideoRequestListForHospitalAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getVideoRequestForHospital(HospitalVideoRequest $videoRequest, HospitalVideoRequestGetForHospitalAction $action)
    {
        $result = $action->execute($videoRequest);

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function storeVideoRequestForHospital(HospitalVideoRequestCreateForHospitalRequest $request, HospitalVideoRequestCreateForHospitalAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function updateVideoRequestForHospital(HospitalVideoRequest $videoRequest, HospitalVideoRequestUpdateForHospitalRequest $request, HospitalVideoRequestUpdateForHospitalAction $action)
    {
        $result = $action->execute($videoRequest, $request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }

    public function cancelVideoRequestForHospital(HospitalVideoRequest $videoRequest, HospitalVideoRequestCancelForHospitalRequest $request, HospitalVideoRequestCancelForHospitalAction $action)
    {
        $result = $action->execute($videoRequest, $request->validated());

        return ApiResponse::success($result['video_request'] ?? $result);
    }
}
