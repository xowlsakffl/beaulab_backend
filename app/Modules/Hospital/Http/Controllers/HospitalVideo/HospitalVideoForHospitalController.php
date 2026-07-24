<?php

namespace App\Modules\Hospital\Http\Controllers\HospitalVideo;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalVideo\Actions\Hospital\HospitalVideoStatusUpdateForHospitalAction;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Modules\Hospital\Http\Requests\HospitalVideo\HospitalVideoStatusUpdateForHospitalRequest;

final class HospitalVideoForHospitalController extends Controller
{
    public function updateVideoStatusForHospital(
        HospitalVideo $video,
        HospitalVideoStatusUpdateForHospitalRequest $request,
        HospitalVideoStatusUpdateForHospitalAction $action,
    ) {
        $result = $action->execute($video, $request->validated());

        return ApiResponse::success($result['video']);
    }
}
