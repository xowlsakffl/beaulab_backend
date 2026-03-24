<?php

namespace App\Modules\Hospital\Http\Controllers\HospitalVideo;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalVideo\Actions\Hospital\HospitalVideoCancelForHospitalAction;
use App\Domains\HospitalVideo\Actions\Hospital\HospitalVideoCreateForHospitalAction;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Modules\Hospital\Http\Requests\HospitalVideo\HospitalVideoCreateForHospitalRequest;

final class HospitalVideoForHospitalController extends Controller
{
    public function storeVideoForHospital(
        HospitalVideoCreateForHospitalRequest $request,
        HospitalVideoCreateForHospitalAction $action
    ) {
        $result = $action->execute($request->user(), $request->validated());

        return ApiResponse::success($result['video'] ?? $result);
    }

    public function cancelVideoForHospital(
        HospitalVideo $video,
        HospitalVideoCancelForHospitalAction $action
    ) {
        $result = $action->execute($video);

        return ApiResponse::success($result['video'] ?? $result);
    }
}
