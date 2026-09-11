<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Controllers\Auth;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountEmailVerificationSendAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountEmailVerificationVerifyAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountInvitationCompleteForHospitalAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountInvitationGetForHospitalAction;
use App\Modules\Hospital\Http\Requests\Auth\CompleteHospitalAccountInvitationRequest;
use App\Modules\Hospital\Http\Requests\Auth\SendHospitalAccountEmailVerificationRequest;
use App\Modules\Hospital\Http\Requests\Auth\VerifyHospitalAccountEmailVerificationRequest;

final class HospitalAccountInvitationForHospitalController extends Controller
{
    /** 병의원 계정 생성 링크의 유효성과 대상 병의원 정보를 확인합니다. */
    public function getHospitalAccountInvitationForHospital(
        string $token,
        HospitalAccountInvitationGetForHospitalAction $action,
    ) {
        $result = $action->execute($token);

        return ApiResponse::success($result['invitation']);
    }

    /** 이메일 인증 증표를 사용해 병의원 계정 생성을 완료합니다. */
    public function completeHospitalAccountInvitationForHospital(
        CompleteHospitalAccountInvitationRequest $request,
        string $token,
        HospitalAccountInvitationCompleteForHospitalAction $action,
    ) {
        return ApiResponse::success($action->execute($token, $request->validated()));
    }

    /** 계정 생성에 사용할 이메일 인증번호를 발송합니다. */
    public function sendHospitalAccountEmailVerificationForHospital(
        SendHospitalAccountEmailVerificationRequest $request,
        string $token,
        HospitalAccountEmailVerificationSendAction $action,
    ) {
        return ApiResponse::success($action->execute($token, (string) $request->validated('email')));
    }

    /** 이메일 인증번호를 확인하고 일회용 계정 생성 증표를 발급합니다. */
    public function verifyHospitalAccountEmailVerificationForHospital(
        VerifyHospitalAccountEmailVerificationRequest $request,
        string $token,
        int $emailVerification,
        HospitalAccountEmailVerificationVerifyAction $action,
    ) {
        return ApiResponse::success($action->execute(
            $token,
            $emailVerification,
            (string) $request->validated('code'),
        ));
    }
}
