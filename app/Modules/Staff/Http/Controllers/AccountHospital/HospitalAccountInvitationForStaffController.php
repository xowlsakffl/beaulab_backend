<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\AccountHospital;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountHospital\Actions\Staff\HospitalAccountInvitationListForStaffAction;
use App\Domains\AccountHospital\Actions\Staff\HospitalAccountInvitationRevokeForStaffAction;
use App\Domains\AccountHospital\Actions\Staff\HospitalAccountInvitationSendForStaffAction;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Modules\Staff\Http\Requests\AccountHospital\HospitalAccountInvitationListForStaffRequest;
use App\Modules\Staff\Http\Requests\AccountHospital\HospitalAccountInvitationSendForStaffRequest;

final class HospitalAccountInvitationForStaffController extends Controller
{
    /** 병의원 또는 입점신청의 계정 초대 발송 이력을 조회합니다. */
    public function getHospitalAccountInvitationsForStaff(
        HospitalAccountInvitationListForStaffRequest $request,
        HospitalAccountInvitationListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta']);
    }

    /** 병의원 계정 생성 링크를 이메일로 발송합니다. */
    public function sendHospitalAccountInvitationForStaff(
        HospitalAccountInvitationSendForStaffRequest $request,
        HospitalAccountInvitationSendForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute(
            $request->user(),
            $request->validated(),
        ));
    }

    /** 아직 사용되지 않은 병의원 계정 초대를 폐기합니다. */
    public function revokeHospitalAccountInvitationForStaff(
        HospitalAccountInvitation $hospitalAccountInvitation,
        HospitalAccountInvitationRevokeForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($hospitalAccountInvitation));
    }
}
