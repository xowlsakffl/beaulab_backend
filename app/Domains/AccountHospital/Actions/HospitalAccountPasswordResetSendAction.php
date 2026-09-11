<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Mail\HospitalAccountPasswordResetMail;
use App\Domains\AccountHospital\Queries\HospitalAccountPasswordResetQuery;
use App\Domains\AccountHospital\Support\AccountHospitalEmail;
use App\Domains\AccountHospital\Support\HospitalAccountMail;
use App\Domains\AccountHospital\Support\HospitalAccountPasswordResetGuard;
use App\Domains\AccountHospital\Support\HospitalAccountPasswordResetToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class HospitalAccountPasswordResetSendAction
{
    public function __construct(private readonly HospitalAccountPasswordResetQuery $query) {}

    public function execute(int $accountId, ?int $staffId = null, ?string $expectedEmail = null, ?string $recipientEmail = null): ?array
    {
        if ($recipientEmail !== null) {
            if ($staffId === null) {
                throw new CustomException(ErrorCode::FORBIDDEN);
            }
            $recipientEmail = AccountHospitalEmail::normalize($recipientEmail);
            if (! AccountHospitalEmail::isValid($recipientEmail)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '수신 이메일 주소를 정확히 입력해 주세요.');
            }
        }
        HospitalAccountMail::assertConfigured();
        $resend = max(1, (int) config('password_reset.hospital.resend_seconds', 60));
        $ttl = max(1, (int) config('password_reset.hospital.expire_minutes', 60));
        $result = DB::transaction(function () use ($accountId, $staffId, $expectedEmail, $recipientEmail, $resend, $ttl) {
            $account = $this->query->lockAccount($accountId);
            if (! HospitalAccountPasswordResetGuard::canReset($account)
                || ($expectedEmail !== null && $account->verifiedEmail() !== $expectedEmail)) {
                if ($staffId !== null) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '인증된 이메일이 있는 활성 병의원 계정만 발송할 수 있습니다.');
                }

                return null;
            }
            $latest = $this->query->latest($accountId);
            if ($latest?->created_at?->gt(now()->subSeconds($resend))) {
                if ($staffId !== null) {
                    throw new CustomException(ErrorCode::RATE_LIMITED, "재설정 링크는 {$resend}초 후 다시 발송할 수 있습니다.");
                }

                return null;
            }
            $token = HospitalAccountPasswordResetToken::make();
            $email = $recipientEmail ?? $account->verifiedEmail();
            $this->query->revokePending($accountId);
            $reset = $this->query->create([
                'account_hospital_id' => $accountId,
                'recipient_email' => $email,
                'token_hash' => HospitalAccountPasswordResetToken::hash($token),
                'credential_hash' => HospitalAccountPasswordResetToken::credentialHash($account),
                'expires_at' => now()->addMinutes($ttl),
                'created_by_staff_id' => $staffId,
            ]);

            return [$reset, $email, (string) $account->hospital->name, $token];
        }, 3);
        if ($result === null) {
            return null;
        }
        [$reset, $email, $hospitalName, $token] = $result;
        try {
            HospitalAccountMail::queue($email, new HospitalAccountPasswordResetMail(
                $hospitalName, HospitalAccountPasswordResetToken::url($token), $ttl,
            ));
        } catch (Throwable $exception) {
            $reset->forceFill(['revoked_at' => now()])->save();
            throw $exception;
        }
        Log::info('병의원 비밀번호 재설정 이메일 발송 접수', [
            'password_reset_id' => $reset->getKey(), 'account_hospital_id' => $accountId, 'staff_id' => $staffId,
        ]);

        return [
            'message' => '비밀번호 재설정 링크 발송을 접수했습니다.',
            'email' => AccountHospitalEmail::mask($email),
            'expires_at' => $reset->expires_at?->toISOString(),
            'resend_after_seconds' => $resend,
        ];
    }
}
