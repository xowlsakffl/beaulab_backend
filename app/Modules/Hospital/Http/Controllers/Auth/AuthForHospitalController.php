<?php

namespace App\Modules\Hospital\Http\Controllers\Auth;

use App\Common\Http\Requests\Auth\PasswordResetLinkSendRequest;
use App\Common\Http\Requests\Auth\PasswordResetRequest;
use App\Common\Http\Requests\Auth\PasswordResetTokenVerifyRequest;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountHospital\Actions\Hospital\GetMyProfileForAccountHospitalAction;
use App\Domains\AccountHospital\Actions\Hospital\LoginForAccountHospitalAction;
use App\Domains\AccountHospital\Actions\Hospital\LogoutForAccountHospitalAction;
use App\Domains\AccountHospital\Actions\Hospital\PasswordUpdateForAccountHospitalAction;
use App\Domains\AccountHospital\Actions\Hospital\ProfileUpdateForAccountHospitalAction;
use App\Domains\Common\PasswordReset\Actions\PasswordResetAction;
use App\Domains\Common\PasswordReset\Actions\PasswordResetLinkSendAction;
use App\Domains\Common\PasswordReset\Actions\PasswordResetTokenVerifyAction;
use App\Domains\Common\PasswordReset\Support\PasswordResetActor;
use App\Modules\Hospital\Http\Requests\Auth\LoginForAccountHospitalRequest;
use App\Modules\Hospital\Http\Requests\Auth\UpdatePasswordForAccountHospitalRequest;
use App\Modules\Hospital\Http\Requests\Auth\UpdateProfileForAccountHospitalRequest;
use Illuminate\Http\Request;

/**
 * AuthForHospitalController 역할 정의.
 * 병원 도메인의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class AuthForHospitalController
{
    public function login(
        LoginForAccountHospitalRequest $request,
        LoginForAccountHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->filters()));
    }

    public function sendPasswordResetLink(
        PasswordResetLinkSendRequest $request,
        PasswordResetLinkSendAction $action
    ) {
        return ApiResponse::success($action->execute(PasswordResetActor::HOSPITAL, $request->filters()));
    }

    public function verifyPasswordResetToken(
        PasswordResetTokenVerifyRequest $request,
        PasswordResetTokenVerifyAction $action
    ) {
        return ApiResponse::success($action->execute(PasswordResetActor::HOSPITAL, $request->filters()));
    }

    public function resetPassword(
        PasswordResetRequest $request,
        PasswordResetAction $action
    ) {
        return ApiResponse::success($action->execute(PasswordResetActor::HOSPITAL, $request->filters()));
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
        ProfileUpdateForAccountHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }

    public function updateMyPassword(
        UpdatePasswordForAccountHospitalRequest $request,
        PasswordUpdateForAccountHospitalAction $action
    ) {
        return ApiResponse::success($action->execute($request->user(), $request->filters()));
    }
}
