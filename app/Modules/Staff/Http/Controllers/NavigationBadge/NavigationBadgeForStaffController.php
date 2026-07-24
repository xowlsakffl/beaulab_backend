<?php

namespace App\Modules\Staff\Http\Controllers\NavigationBadge;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\NavigationBadge\Actions\Staff\NavigationBadgeListForStaffAction;
use Illuminate\Http\Request;

final class NavigationBadgeForStaffController extends Controller
{
    public function getNavigationBadgesForStaff(Request $request, NavigationBadgeListForStaffAction $action)
    {
        return ApiResponse::success($action->execute($request->user()));
    }
}
