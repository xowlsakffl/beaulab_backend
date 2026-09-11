<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\AccountHospital;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountHospital\Actions\Staff\HospitalAccountPasswordResetSendForStaffAction;
use App\Domains\Hospital\Models\Hospital;
use App\Modules\Staff\Http\Requests\AccountHospital\HospitalAccountPasswordResetSendForStaffRequest;

final class HospitalAccountPasswordResetForStaffController extends Controller
{
    /** 관리자 지정 이메일 또는 계정의 인증 이메일로 비밀번호 재설정 링크를 발송합니다. */
    public function sendHospitalAccountPasswordResetLinkForStaff(
        HospitalAccountPasswordResetSendForStaffRequest $request,
        Hospital $hospital,
        HospitalAccountPasswordResetSendForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->user(), $hospital, $request->validated('recipient_email')));
    }
}
