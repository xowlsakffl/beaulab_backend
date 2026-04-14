<?php

namespace App\Modules\Beauty\Http\Controllers\Auth;

use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountBeauty\Actions\Beauty\GetMyProfileForAccountBeautyAction;
use App\Domains\AccountBeauty\Actions\Beauty\LoginForAccountBeautyAction;
use App\Domains\AccountBeauty\Actions\Beauty\LogoutForAccountBeautyAction;
use App\Domains\AccountBeauty\Actions\Beauty\UpdatePasswordForAccountBeautyAction;
use App\Domains\AccountBeauty\Actions\Beauty\UpdateProfileForAccountBeautyAction;
use App\Modules\Beauty\Http\Requests\Auth\LoginForAccountBeautyRequest;
use App\Modules\Beauty\Http\Requests\Auth\UpdatePasswordForAccountBeautyRequest;
use App\Modules\Beauty\Http\Requests\Auth\UpdateProfileForAccountBeautyRequest;
use Illuminate\Http\Request;

/**
 * AuthForBeautyController 역할 정의.
 * 뷰티 도메인의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class AuthForBeautyController
{
    public function login(
        LoginForAccountBeautyRequest $request,
        LoginForAccountBeautyAction $action
    ) {
        return ApiResponse::success($action->execute($request->filters()));
    }

    public function logout(
        Request $request,
        LogoutForAccountBeautyAction $action
    ) {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function getMyProfile(
        Request $request,
        GetMyProfileForAccountBeautyAction $action
    ) {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function updateMyProfile(
        UpdateProfileForAccountBeautyRequest $request,
        UpdateProfileForAccountBeautyAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }

    public function updateMyPassword(
        UpdatePasswordForAccountBeautyRequest $request,
        UpdatePasswordForAccountBeautyAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }
}
