<?php

namespace App\Modules\Staff\Http\Controllers\Dashboard;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;


final class DashboardForStaffController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('permission:common.dashboard.show'),
        ];
    }

    /**
     * GET /api/v1/staff/dashboard
     * (Beaulab) Staff 전용 대쉬보드
     */
    public function getDashboardForStaff(

    ) {
        return ApiResponse::success();
    }
}
