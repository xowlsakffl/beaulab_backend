<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Controllers\Auth;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountPasswordResetAction;
use App\Domains\AccountHospital\Actions\Hospital\HospitalAccountPasswordResetVerifyAction;
use App\Domains\AccountHospital\Jobs\SendHospitalAccountPasswordResetJob;
use App\Domains\AccountHospital\Support\HospitalAccountMail;
use App\Modules\Hospital\Http\Requests\Auth\HospitalAccountPasswordResetRequest;
use App\Modules\Hospital\Http\Requests\Auth\HospitalAccountPasswordResetSendRequest;
use App\Modules\Hospital\Http\Requests\Auth\HospitalAccountPasswordResetVerifyRequest;

final class HospitalAccountPasswordResetForHospitalController extends Controller
{
    public function sendHospitalAccountPasswordReset(HospitalAccountPasswordResetSendRequest $request)
    {
        HospitalAccountMail::assertConfigured();
        SendHospitalAccountPasswordResetJob::dispatch($request->validated('email'))
            ->onConnection((string) config('hospital_account_invitation.mail.connection', 'redis'))
            ->onQueue((string) config('hospital_account_invitation.mail.queue', 'mail'));

        return ApiResponse::success(['message' => '등록된 이메일이라면 비밀번호 재설정 안내를 보내드립니다.']);
    }

    /** 병의원 비밀번호 재설정 링크의 유효성을 확인합니다. */
    public function verifyHospitalAccountPasswordReset(
        HospitalAccountPasswordResetVerifyRequest $request,
        HospitalAccountPasswordResetVerifyAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated('token')));
    }

    /** 인증된 재설정 링크를 소비하고 새 비밀번호를 저장합니다. */
    public function resetHospitalAccountPassword(
        HospitalAccountPasswordResetRequest $request,
        HospitalAccountPasswordResetAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated('token'), $request->validated('password')));
    }
}
