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
