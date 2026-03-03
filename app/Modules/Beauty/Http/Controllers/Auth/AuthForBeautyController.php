<?php

namespace App\Modules\Beauty\Http\Controllers\Auth;

use App\Common\Http\Responses\ApiResponse;
use App\Domains\Beauty\Actions\Auth\GetMyProfileForBeautyAction;
use App\Domains\Beauty\Actions\Auth\LoginForBeautyAction;
use App\Domains\Beauty\Actions\Auth\LogoutForBeautyAction;
use App\Domains\Beauty\Actions\Auth\UpdatePasswordForBeautyAction;
use App\Domains\Beauty\Actions\Auth\UpdateProfileForBeautyAction;
use App\Modules\Beauty\Http\Requests\Auth\LoginForBeautyRequest;
use App\Modules\Beauty\Http\Requests\Auth\UpdatePasswordForBeautyRequest;
use App\Modules\Beauty\Http\Requests\Auth\UpdateProfileForBeautyRequest;
use Illuminate\Http\Request;

final class AuthForBeautyController
{
    public function login(
        LoginForBeautyRequest $request,
        LoginForBeautyAction $action
    ) {
        return ApiResponse::success($action->execute($request->filters()));
    }

    public function logout(
        Request $request,
        LogoutForBeautyAction $action
    ) {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function getMyProfile(
        Request $request,
        GetMyProfileForBeautyAction $action
    ) {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function updateMyProfile(
        UpdateProfileForBeautyRequest $request,
        UpdateProfileForBeautyAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }

    public function updateMyPassword(
        UpdatePasswordForBeautyRequest $request,
        UpdatePasswordForBeautyAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }
}
