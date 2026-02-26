<?php

namespace App\Modules\Partner\Http\Controllers\Auth;

use App\Common\Http\Responses\ApiResponse;
use App\Domains\Partner\Actions\Auth\GetMyProfileForPartnerAction;
use App\Domains\Partner\Actions\Auth\LoginForPartnerAction;
use App\Domains\Partner\Actions\Auth\LogoutForPartnerAction;
use App\Domains\Partner\Actions\Auth\UpdatePasswordForPartnerAction;
use App\Domains\Partner\Actions\Auth\UpdateProfileForPartnerAction;
use App\Modules\Partner\Http\Requests\Auth\LoginForPartnerRequest;
use App\Modules\Partner\Http\Requests\Auth\UpdatePasswordForPartnerRequest;
use App\Modules\Partner\Http\Requests\Auth\UpdateProfileForPartnerRequest;
use Illuminate\Http\Request;

final class AuthForPartnerController
{
    public function login(
        LoginForPartnerRequest $request,
        LoginForPartnerAction $action
    ) {
        return ApiResponse::success($action->execute($request->filters()));
    }

    public function logout(
        Request $request,
        LogoutForPartnerAction $action
    ) {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function getMyProfile(
        Request $request,
        GetMyProfileForPartnerAction $action
    ) {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function updateMyProfile(
        UpdateProfileForPartnerRequest $request,
        UpdateProfileForPartnerAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }

    public function updateMyPassword(
        UpdatePasswordForPartnerRequest $request,
        UpdatePasswordForPartnerAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }
}
