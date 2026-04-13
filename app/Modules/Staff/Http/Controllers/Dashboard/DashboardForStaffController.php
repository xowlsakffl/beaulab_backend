<?php

namespace App\Modules\Staff\Http\Controllers\Dashboard;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * DashboardForStaffController 역할 정의.
 * 스태프 모듈의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
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
