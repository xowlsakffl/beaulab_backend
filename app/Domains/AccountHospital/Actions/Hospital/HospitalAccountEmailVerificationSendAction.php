<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Mail\HospitalAccountEmailVerificationMail;
use App\Domains\AccountHospital\Queries\Hospital\HospitalAccountEmailVerificationQuery;
use App\Domains\AccountHospital\Support\AccountHospitalEmail;
use App\Domains\AccountHospital\Support\HospitalAccountEmailVerificationCode;
use App\Domains\AccountHospital\Support\HospitalAccountMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class HospitalAccountEmailVerificationSendAction
{
    public function __construct(private readonly HospitalAccountEmailVerificationQuery $query) {}

    public function execute(string $invitationToken, string $email): array
    {
        HospitalAccountMail::assertConfigured();
        $email = AccountHospitalEmail::normalize($email);
        $ttl = max(1, (int) config('hospital_account_invitation.email_verification.code_ttl_minutes', 5));
        $resend = max(1, (int) config('hospital_account_invitation.email_verification.resend_seconds', 60));
        $code = HospitalAccountEmailVerificationCode::make();
        $verification = DB::transaction(function () use ($invitationToken, $email, $ttl, $resend, $code) {
            $invitation = $this->query->lockInvitation($invitationToken);
            AccountHospitalEmail::assertAvailable($email);
            $latest = $this->query->latestForUpdate($invitation);
            if ($latest?->created_at?->gt(now()->subSeconds($resend))) {
                throw new CustomException(ErrorCode::RATE_LIMITED, "인증번호는 {$resend}초 후 다시 발송할 수 있습니다.");
            }
            $this->query->invalidatePending($invitation);

            return $this->query->create([
                'hospital_account_invitation_id' => $invitation->getKey(),
                'email' => $email,
                'code_hash' => HospitalAccountEmailVerificationCode::hash($code),
                'code_expires_at' => now()->addMinutes($ttl),
            ]);
        }, 3);
        try {
            HospitalAccountMail::queue($email, new HospitalAccountEmailVerificationMail($code, $ttl));
        } catch (Throwable $exception) {
            $verification->forceFill(['invalidated_at' => now()])->save();
            throw $exception;
        }
        Log::info('병의원 계정 이메일 인증 발송 접수', ['email_verification_id' => $verification->getKey()]);

        return [
            'verification_id' => (int) $verification->getKey(),
            'email' => AccountHospitalEmail::mask($email),
            'code_expires_at' => $verification->code_expires_at?->toISOString(),
            'resend_after_seconds' => $resend,
        ];
    }
}
