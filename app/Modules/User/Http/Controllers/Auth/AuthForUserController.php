<?php

namespace App\Modules\User\Http\Controllers\Auth;

use App\Common\Http\Requests\Auth\PasswordResetLinkSendRequest;
use App\Common\Http\Requests\Auth\PasswordResetRequest;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Actions\User\Auth\GetMyProfileForAccountUserAction;
use App\Domains\AccountUser\Actions\User\Auth\LoginForAccountUserAction;
use App\Domains\AccountUser\Actions\User\Auth\LogoutForAccountUserAction;
use App\Domains\AccountUser\Actions\User\Auth\PasswordUpdateForAccountUserAction;
use App\Domains\AccountUser\Actions\User\Auth\ProfileUpdateForAccountUserAction;
use App\Domains\Common\PasswordReset\Actions\PasswordResetAction;
use App\Domains\Common\PasswordReset\Actions\PasswordResetLinkSendAction;
use App\Domains\Common\PasswordReset\Support\PasswordResetActor;
use App\Modules\User\Http\Requests\Auth\LoginForAccountUserRequest;
use App\Modules\User\Http\Requests\Auth\UpdatePasswordForAccountUserRequest;
use App\Modules\User\Http\Requests\Auth\UpdateProfileForAccountUserRequest;
use Illuminate\Http\Request;

/**
 * 앱 사용자 인증 API 컨트롤러.
 * 요청 검증은 FormRequest, 실제 비즈니스 흐름은 Domain Action에 위임한다.
 */
final class AuthForUserController
{
    public function login(LoginForAccountUserRequest $request, LoginForAccountUserAction $action)
    {
        return ApiResponse::success($action->execute($request->filters()));
    }

    public function sendPasswordResetLink(PasswordResetLinkSendRequest $request, PasswordResetLinkSendAction $action)
    {
        return ApiResponse::success($action->execute(PasswordResetActor::USER, $request->filters()));
    }

    public function resetPassword(PasswordResetRequest $request, PasswordResetAction $action)
    {
        return ApiResponse::success($action->execute(PasswordResetActor::USER, $request->filters()));
    }

    public function logout(Request $request, LogoutForAccountUserAction $action)
    {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function getMyProfile(Request $request, GetMyProfileForAccountUserAction $action)
    {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function updateMyProfile(
        UpdateProfileForAccountUserRequest $request,
        ProfileUpdateForAccountUserAction $action,
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }

    public function updateMyPassword(
        UpdatePasswordForAccountUserRequest $request,
        PasswordUpdateForAccountUserAction $action,
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }
}
