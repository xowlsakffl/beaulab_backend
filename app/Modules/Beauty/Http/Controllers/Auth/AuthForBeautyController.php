<?php

namespace App\Modules\Beauty\Http\Controllers\Auth;

use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountBeauty\Actions\Auth\GetMyProfileForAccountBeautyAction;
use App\Domains\AccountBeauty\Actions\Auth\LoginForAccountBeautyAction;
use App\Domains\AccountBeauty\Actions\Auth\LogoutForAccountBeautyAction;
use App\Domains\AccountBeauty\Actions\Auth\UpdatePasswordForAccountBeautyAction;
use App\Domains\AccountBeauty\Actions\Auth\UpdateProfileForAccountBeautyAction;
use App\Modules\Beauty\Http\Requests\Auth\LoginForAccountBeautyRequest;
use App\Modules\Beauty\Http\Requests\Auth\UpdatePasswordForAccountBeautyRequest;
use App\Modules\Beauty\Http\Requests\Auth\UpdateProfileForAccountBeautyRequest;
use Illuminate\Http\Request;

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