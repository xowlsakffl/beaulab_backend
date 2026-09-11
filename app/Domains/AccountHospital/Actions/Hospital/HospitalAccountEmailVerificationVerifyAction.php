<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\HospitalAccountEmailVerification;
use App\Domains\AccountHospital\Queries\Hospital\HospitalAccountEmailVerificationQuery;
use App\Domains\AccountHospital\Support\HospitalAccountEmailVerificationCode;
use App\Domains\AccountHospital\Support\HospitalAccountEmailVerificationToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class HospitalAccountEmailVerificationVerifyAction
{
    public function __construct(
        private readonly HospitalAccountEmailVerificationQuery $query,
    ) {}

    /**
     * @return array{email_verification_token:string,email:string,expires_at:?string}
     */
    public function execute(string $invitationToken, int $verificationId, string $code): array
    {
        $maxAttempts = max(1, (int) config('hospital_account_invitation.email_verification.max_attempts', 5));
        $verificationTtlMinutes = max(
            1,
            (int) config('hospital_account_invitation.email_verification.verification_ttl_minutes', 15),
        );

        $result = DB::transaction(function () use (
            $invitationToken,
            $verificationId,
            $code,
            $maxAttempts,
            $verificationTtlMinutes,
        ): array {
            $invitation = $this->query->lockInvitation($invitationToken);
            $verification = $this->query->lockVerification($invitation, $verificationId);

            if (! $verification instanceof HospitalAccountEmailVerification || ! $verification->isCodeUsable()) {
                return ['error' => '인증번호가 유효하지 않거나 만료되었습니다. 다시 발송해 주세요.'];
            }

            if ((int) $verification->failed_attempt_count >= $maxAttempts) {
                $verification->forceFill(['invalidated_at' => now()])->save();

                return ['error' => '인증번호 입력 횟수를 초과했습니다. 다시 발송해 주세요.'];
            }

            if (! HospitalAccountEmailVerificationCode::verify($code, (string) $verification->code_hash)) {
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

            $rawToken = HospitalAccountEmailVerificationToken::make();
            $verification->forceFill([
                'verification_token_hash' => HospitalAccountEmailVerificationToken::hash($rawToken),
                'verified_at' => now(),
                'verification_expires_at' => now()->addMinutes($verificationTtlMinutes),
                'failed_attempt_count' => 0,
            ])->save();

            return [
                'verification' => $verification,
                'email_verification_token' => $rawToken,
            ];
        }, 3);

        if (isset($result['error'])) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, (string) $result['error']);
        }

        /** @var HospitalAccountEmailVerification $verification */
        $verification = $result['verification'];

        Log::info('병의원 계정 이메일 인증 완료', [
            'invitation_id' => $verification->hospital_account_invitation_id,
            'email_verification_id' => $verification->getKey(),
        ]);

        return [
            'email_verification_token' => (string) $result['email_verification_token'],
            'email' => (string) $verification->email,
            'expires_at' => $verification->verification_expires_at?->toISOString(),
        ];
    }
}
