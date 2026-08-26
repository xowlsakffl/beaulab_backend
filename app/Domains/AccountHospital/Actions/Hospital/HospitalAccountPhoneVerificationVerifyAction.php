<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\HospitalAccountPhoneVerification;
use App\Domains\AccountHospital\Queries\Hospital\HospitalAccountPhoneVerificationQuery;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationGuard;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationToken;
use App\Domains\AccountHospital\Support\HospitalAccountPhoneVerificationCode;
use App\Domains\AccountHospital\Support\HospitalAccountPhoneVerificationToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class HospitalAccountPhoneVerificationVerifyAction
{
    public function __construct(
        private readonly HospitalAccountPhoneVerificationQuery $query,
    ) {}

    /**
     * @return array{phone_verification_token:string,phone:string,expires_at:?string}
     */
    public function execute(string $invitationToken, int $verificationId, string $code): array
    {
        $maxAttempts = max(1, (int) config('hospital_account_invitation.phone_verification.max_attempts', 5));
        $verificationTtlMinutes = max(
            1,
            (int) config('hospital_account_invitation.phone_verification.verification_ttl_minutes', 15),
        );

        $result = DB::transaction(function () use (
            $invitationToken,
            $verificationId,
            $code,
            $maxAttempts,
            $verificationTtlMinutes,
        ): array {
            $invitation = HospitalAccountInvitationGuard::assertActive(
                $this->query->lockInvitationByTokenHash(HospitalAccountInvitationToken::hash($invitationToken))
            );
            $verification = $this->query->lockVerification((int) $invitation->getKey(), $verificationId);

            if (! $verification instanceof HospitalAccountPhoneVerification || ! $verification->isCodeUsable()) {
                return ['error' => '인증번호가 유효하지 않거나 만료되었습니다. 다시 발송해 주세요.'];
            }

            if ((int) $verification->failed_attempt_count >= $maxAttempts) {
                $verification->forceFill(['invalidated_at' => now()])->save();

                return ['error' => '인증번호 입력 횟수를 초과했습니다. 다시 발송해 주세요.'];
            }

            if (! HospitalAccountPhoneVerificationCode::verify($code, (string) $verification->code_hash)) {
                $failedAttempts = (int) $verification->failed_attempt_count + 1;
                $verification->forceFill([
                    'failed_attempt_count' => $failedAttempts,
                    'invalidated_at' => $failedAttempts >= $maxAttempts ? now() : null,
                ])->save();

                return [
                    'error' => $failedAttempts >= $maxAttempts
                        ? '인증번호 입력 횟수를 초과했습니다. 다시 발송해 주세요.'
                        : '인증번호가 일치하지 않습니다.',
                ];
            }

            $rawToken = HospitalAccountPhoneVerificationToken::make();
            $verification->forceFill([
                'verification_token_hash' => HospitalAccountPhoneVerificationToken::hash($rawToken),
                'verified_at' => now(),
                'verification_expires_at' => now()->addMinutes($verificationTtlMinutes),
                'failed_attempt_count' => 0,
            ])->save();

            return [
                'verification' => $verification,
                'phone_verification_token' => $rawToken,
            ];
        }, 3);

        if (isset($result['error'])) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, (string) $result['error']);
        }

        /** @var HospitalAccountPhoneVerification $verification */
        $verification = $result['verification'];

        Log::info('병의원 계정 생성 휴대폰 인증 완료', [
            'invitation_id' => $verification->hospital_account_invitation_id,
            'phone_verification_id' => $verification->getKey(),
        ]);

        return [
            'phone_verification_token' => (string) $result['phone_verification_token'],
            'phone' => (string) $verification->phone,
            'expires_at' => $verification->verification_expires_at?->toISOString(),
        ];
    }
}
