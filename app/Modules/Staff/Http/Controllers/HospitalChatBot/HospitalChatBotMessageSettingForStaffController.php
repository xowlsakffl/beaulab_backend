<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalChatBot;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotMessageSettingForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalChatBot\HospitalChatBotMessageSettingForStaffRequest;

final class HospitalChatBotMessageSettingForStaffController extends Controller
{
    public function macros(HospitalChatBotMessageSettingForStaffAction $action)
    {
        return ApiResponse::success($action->macros());
    }

    public function show(HospitalChatBotMessageSettingForStaffAction $action)
    {
        return ApiResponse::success($action->show());
    }

    public function update(HospitalChatBotMessageSettingForStaffRequest $request, HospitalChatBotMessageSettingForStaffAction $action)
    {
        return ApiResponse::success($action->update($request->validated(), (int) $request->user()->id));
    }
}
