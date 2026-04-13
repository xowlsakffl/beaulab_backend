<?php

namespace App\Modules\Staff\Http\Controllers\Auth;

use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountStaff\Actions\Auth\GetMyProfileForStaffAction;
use App\Domains\AccountStaff\Actions\Auth\LoginForStaffAction;
use App\Domains\AccountStaff\Actions\Auth\LogoutForStaffAction;
use App\Domains\AccountStaff\Actions\Auth\UpdatePasswordForStaffAction;
use App\Domains\AccountStaff\Actions\Auth\UpdateProfileForStaffAction;
use App\Modules\Staff\Http\Requests\Auth\LoginForStaffRequest;
use App\Modules\Staff\Http\Requests\Auth\UpdatePasswordForStaffRequest;
use App\Modules\Staff\Http\Requests\Auth\UpdateProfileForStaffRequest;
use Illuminate\Http\Request;

/**
 * AuthForStaffController 역할 정의.
 * 스태프 모듈의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class AuthForStaffController
{
    public function login(
        LoginForStaffRequest $request,
        LoginForStaffAction $action
    ) {
        $filters = $request->filters();

        $payload = $action->execute($filters);

        return ApiResponse::success($payload);
    }

    public function logout(
        Request $request,
        LogoutForStaffAction $action
    ) {
        $payload = $action->execute($request->user());

        return ApiResponse::success($payload);
    }

    public function getMyProfile(
        Request $request,
        GetMyProfileForStaffAction $action
    ) {
        $staff = $request->user();
        $payload = $action->execute($staff);

        return ApiResponse::success($payload);
    }

    public function updateMyProfile(
        UpdateProfileForStaffRequest $request,
        UpdateProfileForStaffAction  $action
    ) {

        $staff = $request->user();

        return ApiResponse::success($action->execute($staff, $request->filters()));
    }

    public function updateMyPassword(
        UpdatePasswordForStaffRequest $request,
        UpdatePasswordForStaffAction $action
    ) {

        $staff = $request->user();

        return ApiResponse::success($action->execute($staff, $request->filters()));
    }
}
