<?php

namespace App\Modules\Staff\Http\Controllers\HospitalTalk;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalTalk\Actions\Staff\HospitalTalkCreateForStaffAction;
use App\Domains\HospitalTalk\Actions\Staff\HospitalTalkDeleteForStaffAction;
use App\Domains\HospitalTalk\Actions\Staff\HospitalTalkGetForStaffAction;
use App\Domains\HospitalTalk\Actions\Staff\HospitalTalkListForStaffAction;
use App\Domains\HospitalTalk\Actions\Staff\HospitalTalkUpdateForStaffAction;
use App\Domains\HospitalTalk\Models\HospitalTalk;
use App\Modules\Staff\Http\Requests\HospitalTalk\HospitalTalkCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalTalk\HospitalTalkGetForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalTalk\HospitalTalkListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalTalk\HospitalTalkUpdateForStaffRequest;

final class HospitalTalkForStaffController extends Controller
{
    public function getTalksForStaff(HospitalTalkListForStaffRequest $request, HospitalTalkListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getTalkForStaff(HospitalTalk $talk, HospitalTalkGetForStaffRequest $request, HospitalTalkGetForStaffAction $action)
    {
        $result = $action->execute($talk, $request->filters());

        return ApiResponse::success($result['talk'] ?? $result);
    }

    public function storeTalkForStaff(HospitalTalkCreateForStaffRequest $request, HospitalTalkCreateForStaffAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['talk'] ?? $result);
    }

    public function updateTalkForStaff(HospitalTalk $talk, HospitalTalkUpdateForStaffRequest $request, HospitalTalkUpdateForStaffAction $action)
    {
        $result = $action->execute($talk, $request->validated());

        return ApiResponse::success($result['talk'] ?? $result);
    }

    public function deleteTalkForStaff(HospitalTalk $talk, HospitalTalkDeleteForStaffAction $action)
    {
        $result = $action->execute($talk);

        return ApiResponse::success($result);
    }
}
