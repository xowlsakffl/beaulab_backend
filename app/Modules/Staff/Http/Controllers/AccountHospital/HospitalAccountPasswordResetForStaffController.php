<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\AccountHospital;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountHospital\Actions\Staff\HospitalAccountPasswordResetSendForStaffAction;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Http\Request;

final class HospitalAccountPasswordResetForStaffController extends Controller
{
    /** 병의원 계정의 인증된 휴대폰 번호로 비밀번호 재설정 링크를 발송합니다. */
    public function sendHospitalAccountPasswordResetLinkForStaff(
        Request $request,
        Hospital $hospital,
        HospitalAccountPasswordResetSendForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->user(), $hospital));
    }
}
