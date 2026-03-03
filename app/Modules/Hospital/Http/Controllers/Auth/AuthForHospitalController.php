<?php

namespace App\Modules\Hospital\Http\Controllers\Auth;

use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountHospital\Actions\Auth\GetMyProfileForAccountHospitalAction;
use App\Domains\AccountHospital\Actions\Auth\LoginForAccountHospitalAction;
use App\Domains\AccountHospital\Actions\Auth\LogoutForAccountHospitalAction;
use App\Domains\AccountHospital\Actions\Auth\UpdatePasswordForAccountHospitalAction;
use App\Domains\AccountHospital\Actions\Auth\UpdateProfileForAccountHospitalAction;
use App\Modules\Hospital\Http\Requests\Auth\LoginForAccountHospitalRequest;
use App\Modules\Hospital\Http\Requests\Auth\UpdatePasswordForAccountHospitalRequest;
use App\Modules\Hospital\Http\Requests\Auth\UpdateProfileForAccountHospitalRequest;
use Illuminate\Http\Request;

final class AuthForHospitalController
{
    public function login(
        LoginForAccountHospitalRequest $request,
        LoginForAccountHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->filters()));
    }

    public function logout(
        Request $request,
        LogoutForAccountHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function getMyProfile(
        Request $request,
        GetMyProfileForAccountHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function updateMyProfile(
        UpdateProfileForAccountHospitalRequest $request,
        UpdateProfileForAccountHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }

    public function updateMyPassword(
        UpdatePasswordForAccountHospitalRequest $request,
        UpdatePasswordForAccountHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }
}
