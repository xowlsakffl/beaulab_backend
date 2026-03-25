<?php

namespace App\Modules\Staff\Http\Controllers\HospitalVideo;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalVideo\Actions\Staff\HospitalVideoCreateForStaffAction;
use App\Domains\HospitalVideo\Actions\Staff\HospitalVideoDownloadVideoFileForStaffAction;
use App\Domains\HospitalVideo\Actions\Staff\HospitalVideoDoctorOptionListForStaffAction;
use App\Domains\HospitalVideo\Actions\Staff\HospitalVideoHospitalOptionListForStaffAction;
use App\Domains\HospitalVideo\Actions\Staff\HospitalVideoDeleteForStaffAction;
use App\Domains\HospitalVideo\Actions\Staff\HospitalVideoGetForStaffAction;
use App\Domains\HospitalVideo\Actions\Staff\HospitalVideoListForStaffAction;
use App\Domains\HospitalVideo\Actions\Staff\HospitalVideoUpdateForStaffAction;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Modules\Staff\Http\Requests\HospitalVideo\HospitalVideoCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalVideo\HospitalVideoDoctorOptionListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalVideo\HospitalVideoHospitalOptionListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalVideo\HospitalVideoListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalVideo\HospitalVideoUpdateForStaffRequest;

final class HospitalVideoForStaffController extends Controller
{
    public function getVideoHospitalOptionsForStaff(
        HospitalVideoHospitalOptionListForStaffRequest $request,
        HospitalVideoHospitalOptionListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getVideoDoctorOptionsForStaff(
        HospitalVideoDoctorOptionListForStaffRequest $request,
        HospitalVideoDoctorOptionListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getVideosForStaff(HospitalVideoListForStaffRequest $request, HospitalVideoListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getVideoForStaff(HospitalVideo $video, HospitalVideoGetForStaffAction $action)
    {
        $result = $action->execute($video);

        return ApiResponse::success($result['video'] ?? $result);
    }

    public function getVideoForEditForStaff(HospitalVideo $video, HospitalVideoGetForStaffAction $action)
    {
        $result = $action->execute($video, 'update');

        return ApiResponse::success($result['video'] ?? $result);
    }

    public function downloadVideoFileForStaff(HospitalVideo $video, HospitalVideoDownloadVideoFileForStaffAction $action)
    {
        return $action->execute($video);
    }

    public function storeVideoForStaff(HospitalVideoCreateForStaffRequest $request, HospitalVideoCreateForStaffAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['video'] ?? $result);
    }

    public function updateVideoForStaff(HospitalVideo $video, HospitalVideoUpdateForStaffRequest $request, HospitalVideoUpdateForStaffAction $action)
    {
        $result = $action->execute($video, $request->validated());

        return ApiResponse::success($result['video'] ?? $result);
    }

    public function deleteVideoForStaff(HospitalVideo $video, HospitalVideoDeleteForStaffAction $action)
    {
        $result = $action->execute($video);

        return ApiResponse::success($result);
    }
}
