<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Controllers\Auth;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountInvitationCompleteForHospitalAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountInvitationGetForHospitalAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountPhoneVerificationSendAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountPhoneVerificationVerifyAction;
use App\Modules\Hospital\Http\Requests\Auth\CompleteHospitalAccountInvitationRequest;
use App\Modules\Hospital\Http\Requests\Auth\SendHospitalAccountPhoneVerificationRequest;
use App\Modules\Hospital\Http\Requests\Auth\VerifyHospitalAccountPhoneVerificationRequest;

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

    /** 휴대폰 문자 인증 증표를 사용해 병의원 계정 생성을 완료합니다. */
    public function completeHospitalAccountInvitationForHospital(
        CompleteHospitalAccountInvitationRequest $request,
        string $token,
        HospitalAccountInvitationCompleteForHospitalAction $action,
    ) {
        return ApiResponse::success($action->execute($token, $request->validated()));
    }

    /** 계정 생성에 사용할 휴대폰 인증번호를 발송합니다. */
    public function sendHospitalAccountPhoneVerificationForHospital(
        SendHospitalAccountPhoneVerificationRequest $request,
        string $token,
        HospitalAccountPhoneVerificationSendAction $action,
    ) {
        return ApiResponse::success($action->execute($token, (string) $request->validated('phone')));
    }

    /** 휴대폰 인증번호를 확인하고 일회용 계정 생성 증표를 발급합니다. */
    public function verifyHospitalAccountPhoneVerificationForHospital(
        VerifyHospitalAccountPhoneVerificationRequest $request,
        string $token,
        int $phoneVerification,
        HospitalAccountPhoneVerificationVerifyAction $action,
    ) {
        return ApiResponse::success($action->execute(
            $token,
            $phoneVerification,
            (string) $request->validated('code'),
        ));
    }
}
