<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Models\HospitalAccountPasswordReset;
use App\Domains\AccountHospital\Queries\HospitalAccountPasswordResetQuery;
use App\Domains\AccountHospital\Support\AccountHospitalPhone;
use App\Domains\AccountHospital\Support\HospitalAccountPasswordResetGuard;
use App\Domains\AccountHospital\Support\HospitalAccountPasswordResetToken;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Sms\Actions\SmsBatchCreateAction;
use App\Domains\Common\Sms\Data\SmsDeliveryData;
use App\Domains\Common\Sms\Models\SmsDelivery;
use App\Domains\Common\Sms\Support\SmsMessage;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class HospitalAccountPasswordResetSendForStaffAction
{
    public function __construct(
        private readonly HospitalAccountPasswordResetQuery $query,
        private readonly SmsBatchCreateAction $smsBatchCreate,
    ) {}

    /** @return array{message:string,phone:string,expires_at:?string,resend_after_seconds:int} */
    public function execute(AccountStaff $actor, Hospital $hospital): array
    {
        Gate::forUser($actor)->authorize('sendPasswordResetLink', $hospital);
        if (! config('sms.enabled') || (app()->environment('production') && config('sms.provider') === 'log')) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '문자 발송이 설정되지 않았습니다.');
        }

        $resendSeconds = max(1, (int) config('password_reset.hospital.resend_seconds', 60));
        $expireMinutes = max(1, (int) config('password_reset.hospital.expire_minutes', 60));
        [$reset, $phone] = DB::transaction(function () use ($actor, $hospital, $resendSeconds, $expireMinutes): array {
            $account = $this->query->lockAccountForHospital((int) $hospital->getKey());
            if (! HospitalAccountPasswordResetGuard::canReset($account)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '인증된 휴대폰 번호가 있는 활성 병의원 계정만 발송할 수 있습니다.');
            }

            $latest = $this->query->latest((int) $account->getKey());
            if ($latest?->created_at?->gt(now()->subSeconds($resendSeconds))) {
                throw new CustomException(ErrorCode::RATE_LIMITED, "재설정 링크는 {$resendSeconds}초 후 다시 발송할 수 있습니다.");
            }

            $token = HospitalAccountPasswordResetToken::make();
            $this->query->revokePending((int) $account->getKey());
            $reset = $this->query->create([
                'account_hospital_id' => $account->getKey(),
                'token_hash' => HospitalAccountPasswordResetToken::hash($token),
                'credential_hash' => HospitalAccountPasswordResetToken::credentialHash($account),
                'expires_at' => now()->addMinutes($expireMinutes),
                'created_by_staff_id' => $actor->getKey(),
            ]);
            $this->queueSms($actor, $account, $reset, $token, $expireMinutes);

            return [$reset, (string) $account->verifiedPhone()];
        }, 3);

        Log::info('병의원 계정 비밀번호 재설정 문자 발송 접수', [
            'password_reset_id' => $reset->getKey(),
            'account_hospital_id' => $reset->account_hospital_id,
            'staff_id' => $actor->getKey(),
        ]);

        return [
            'message' => '인증된 휴대폰 번호로 비밀번호 재설정 링크 발송을 접수했습니다.',
            'phone' => preg_replace('/^(01\d)\d{3,4}(\d{4})$/', '$1-****-$2', AccountHospitalPhone::normalize($phone)),
            'expires_at' => $reset->expires_at?->toISOString(),
            'resend_after_seconds' => $resendSeconds,
        ];
    }

    private function queueSms(
        AccountStaff $actor,
        AccountHospital $account,
        HospitalAccountPasswordReset $reset,
        string $token,
        int $expireMinutes,
    ): void {
        $message = "[뷰랩] 병의원 계정 비밀번호 재설정\n".HospitalAccountPasswordResetToken::url($token)
            ."\n{$expireMinutes}분 내 변경해 주세요. 요청하지 않았다면 이 문자를 무시해 주세요.";
        $phone = (string) $account->verifiedPhone();
        $this->smsBatchCreate->execute(
            purpose: 'hospital_account_password_reset',
            idempotencyKey: (string) Str::uuid(),
            messageTemplate: '[뷰랩] 병의원 계정 비밀번호 재설정',
            metadata: ['password_reset_id' => (int) $reset->getKey()],
            targetCount: 1,
            actor: $actor,
            resolveDeliveries: fn (): array => [new SmsDeliveryData(
                deduplicationKey: "password-reset:{$reset->getKey()}",
                referenceType: $account->getMorphClass(),
                referenceId: (int) $account->getKey(),
                referenceLabel: (string) $account->hospital->name,
                recipientType: $account->getMorphClass(),
                recipientId: (int) $account->getKey(),
                recipientKinds: ['PASSWORD_RESET'],
                phone: $phone,
                phoneNormalized: AccountHospitalPhone::normalize($phone),
                messageType: SmsMessage::type($message),
                messageBody: '[보안 문자] 병의원 계정 비밀번호 재설정 링크',
                byteLength: SmsMessage::byteLength($message),
                status: SmsDelivery::STATUS_PENDING,
                encryptedMessageBody: $message,
            )],
        );
    }
}
