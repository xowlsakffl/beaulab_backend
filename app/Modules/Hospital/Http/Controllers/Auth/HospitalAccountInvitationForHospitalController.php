<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Controllers\Auth;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountInvitationCompleteForHospitalAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountInvitationGetForHospitalAction;
use App\Modules\Hospital\Http\Requests\Auth\CompleteHospitalAccountInvitationRequest;

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

    /** 휴대폰 본인확인 증표를 사용해 병의원 계정 생성을 완료합니다. */
    public function completeHospitalAccountInvitationForHospital(
        CompleteHospitalAccountInvitationRequest $request,
        string $token,
        HospitalAccountInvitationCompleteForHospitalAction $action,
    ) {
        return ApiResponse::success($action->execute($token, $request->validated()));
    }
}
