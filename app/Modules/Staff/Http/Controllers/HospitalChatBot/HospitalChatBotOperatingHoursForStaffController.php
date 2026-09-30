<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalChatBot;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotOperatingHoursGetForStaffAction;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotOperatingHoursUpdateForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalChatBot\HospitalChatBotOperatingHoursUpdateForStaffRequest;

final class HospitalChatBotOperatingHoursForStaffController extends Controller
{
    public function show(HospitalChatBotOperatingHoursGetForStaffAction $action)
    {
        return ApiResponse::success($action->execute());
    }

    public function update(
        HospitalChatBotOperatingHoursUpdateForStaffRequest $request,
        HospitalChatBotOperatingHoursUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->settings()));
    }
}
