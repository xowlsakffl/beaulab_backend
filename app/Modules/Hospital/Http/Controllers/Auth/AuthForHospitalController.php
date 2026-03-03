<?php

namespace App\Modules\Hospital\Http\Controllers\Auth;

use App\Common\Http\Responses\ApiResponse;
use App\Domains\Hospital\Actions\Auth\GetMyProfileForHospitalAction;
use App\Domains\Hospital\Actions\Auth\LoginForHospitalAction;
use App\Domains\Hospital\Actions\Auth\LogoutForHospitalAction;
use App\Domains\Hospital\Actions\Auth\UpdatePasswordForHospitalAction;
use App\Domains\Hospital\Actions\Auth\UpdateProfileForHospitalAction;
use App\Modules\Hospital\Http\Requests\Auth\LoginForHospitalRequest;
use App\Modules\Hospital\Http\Requests\Auth\UpdatePasswordForHospitalRequest;
use App\Modules\Hospital\Http\Requests\Auth\UpdateProfileForHospitalRequest;
use Illuminate\Http\Request;

final class AuthForHospitalController
{
    public function login(
        LoginForHospitalRequest $request,
        LoginForHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->filters()));
    }

    public function logout(
        Request $request,
        LogoutForHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function getMyProfile(
        Request $request,
        GetMyProfileForHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function updateMyProfile(
        UpdateProfileForHospitalRequest $request,
        UpdateProfileForHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }

    public function updateMyPassword(
        UpdatePasswordForHospitalRequest $request,
        UpdatePasswordForHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }
}
