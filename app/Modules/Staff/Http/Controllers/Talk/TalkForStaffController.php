<?php

namespace App\Modules\Staff\Http\Controllers\Talk;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Talk\Actions\Staff\TalkCreateForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkDeleteForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkGetForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkListForStaffAction;
use App\Domains\Talk\Actions\Staff\TalkUpdateForStaffAction;
use App\Domains\Talk\Models\Talk;
use App\Modules\Staff\Http\Requests\Talk\TalkCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\Talk\TalkGetForStaffRequest;
use App\Modules\Staff\Http\Requests\Talk\TalkListForStaffRequest;
use App\Modules\Staff\Http\Requests\Talk\TalkUpdateForStaffRequest;

final class TalkForStaffController extends Controller
{
    public function getTalksForStaff(TalkListForStaffRequest $request, TalkListForStaffAction $action)
    {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getTalkForStaff(Talk $talk, TalkGetForStaffRequest $request, TalkGetForStaffAction $action)
    {
        $result = $action->execute($talk, $request->filters());

        return ApiResponse::success($result['talk'] ?? $result);
    }

    public function storeTalkForStaff(TalkCreateForStaffRequest $request, TalkCreateForStaffAction $action)
    {
        $result = $action->execute($request->validated());

        return ApiResponse::success($result['talk'] ?? $result);
    }

    public function updateTalkForStaff(Talk $talk, TalkUpdateForStaffRequest $request, TalkUpdateForStaffAction $action)
    {
        $result = $action->execute($talk, $request->validated());

        return ApiResponse::success($result['talk'] ?? $result);
    }

    public function deleteTalkForStaff(Talk $talk, TalkDeleteForStaffAction $action)
    {
        $result = $action->execute($talk);

        return ApiResponse::success($result);
    }
}
