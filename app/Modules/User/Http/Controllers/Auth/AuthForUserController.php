<?php

namespace App\Modules\User\Http\Controllers\Auth;

use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Actions\Auth\GetMyProfileForAccountUserAction;
use App\Domains\AccountUser\Actions\Auth\LoginForAccountUserAction;
use App\Domains\AccountUser\Actions\Auth\LogoutForAccountUserAction;
use App\Domains\AccountUser\Actions\Auth\UpdatePasswordForAccountUserAction;
use App\Domains\AccountUser\Actions\Auth\UpdateProfileForAccountUserAction;
use App\Modules\User\Http\Requests\Auth\LoginForAccountUserRequest;
use App\Modules\User\Http\Requests\Auth\UpdatePasswordForAccountUserRequest;
use App\Modules\User\Http\Requests\Auth\UpdateProfileForAccountUserRequest;
use Illuminate\Http\Request;

final class AuthForUserController
{
    public function login(LoginForAccountUserRequest $request, LoginForAccountUserAction $action)
    {
        return ApiResponse::success($action->execute($request->filters()));
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
        UpdateProfileForAccountUserAction $action,
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }

    public function updateMyPassword(
        UpdatePasswordForAccountUserRequest $request,
        UpdatePasswordForAccountUserAction $action,
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }
}
