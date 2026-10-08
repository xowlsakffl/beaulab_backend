<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalChatBot;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\HospitalChatBot\Actions\Staff\HospitalChatBotDashboardForStaffAction;
use App\Modules\Staff\Http\Requests\HospitalChatBot\HospitalChatBotDashboardForStaffRequest;

final class HospitalChatBotDashboardForStaffController extends Controller
{
    public function show(
        HospitalChatBotDashboardForStaffRequest $request,
        HospitalChatBotDashboardForStaffAction $action,
    ) {
        // 화면 전체를 한 번에 돌려준다. 카드 일곱 개를 위해 일곱 번 부르지 않는다.
        return ApiResponse::success($action->execute($request->filters()));
    }
}
