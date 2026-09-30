<?php

namespace App\Modules\Staff\Http\Controllers\AccountUser;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Actions\Staff\AccountUserStatisticsForStaffAction;
use App\Modules\Staff\Http\Requests\AccountUser\AccountUserStatisticsForStaffRequest;

final class AccountUserStatisticsForStaffController extends Controller
{
    public function show(string $section, AccountUserStatisticsForStaffRequest $request, AccountUserStatisticsForStaffAction $action)
    {
        return ApiResponse::success($action->execute($section, $request->validated('period') ?? 'daily'));
    }
}
