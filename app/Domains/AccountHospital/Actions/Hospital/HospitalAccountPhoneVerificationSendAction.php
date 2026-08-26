<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Models\HospitalAccountPhoneVerification;
use App\Domains\AccountHospital\Queries\Hospital\HospitalAccountPhoneVerificationQuery;
use App\Domains\AccountHospital\Support\AccountHospitalPhone;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationGuard;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationToken;
use App\Domains\AccountHospital\Support\HospitalAccountPhoneVerificationCode;
use App\Domains\Common\Sms\Actions\SmsBatchCreateAction;
use App\Domains\Common\Sms\Data\SmsDeliveryData;
use App\Domains\Common\Sms\Models\SmsDelivery;
use App\Domains\Common\Sms\Support\SmsMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class HospitalAccountPhoneVerificationSendAction
{
    public function __construct(
        private readonly HospitalAccountPhoneVerificationQuery $query,
        private readonly SmsBatchCreateAction $smsBatchCreateAction,
    ) {}

    /**
     * @return array{verification_id:int,phone:string,code_expires_at:?string,resend_after_seconds:int}
     */
    public function execute(string $invitationToken, string $phone): array
    {
        $phone = AccountHospitalPhone::format($phone);
        if (! AccountHospitalPhone::isValid($phone)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '휴대폰 번호 형식이 올바르지 않습니다.');
        }

        $code = HospitalAccountPhoneVerificationCode::make();
        $codeTtlMinutes = max(1, (int) config('hospital_account_invitation.phone_verification.code_ttl_minutes', 5));
        $resendSeconds = max(1, (int) config('hospital_account_invitation.phone_verification.resend_seconds', 60));

        $verification = DB::transaction(function () use (
            $invitationToken,
            $phone,
            $code,
            $codeTtlMinutes,
            $resendSeconds,
        ): HospitalAccountPhoneVerification {
            $invitation = HospitalAccountInvitationGuard::assertActive(
                $this->query->lockInvitationByTokenHash(HospitalAccountInvitationToken::hash($invitationToken))
            );

            $latest = $this->query->latestForUpdate((int) $invitation->getKey(), $phone);
            if ($latest?->created_at?->isAfter(now()->subSeconds($resendSeconds)) === true) {
                throw new CustomException(
                    ErrorCode::INVALID_REQUEST,
                    "인증번호는 {$resendSeconds}초 후 다시 발송할 수 있습니다.",
                );
            }

            $this->query->invalidatePending((int) $invitation->getKey());

            return $this->query->create([
                'hospital_account_invitation_id' => $invitation->getKey(),
                'phone' => $phone,
                'code_hash' => HospitalAccountPhoneVerificationCode::hash($code),
                'failed_attempt_count' => 0,
                'code_expires_at' => now()->addMinutes($codeTtlMinutes),
            ]);
        }, 3);

        try {
            $this->queueSms($verification, $code, $codeTtlMinutes);
        } catch (Throwable $exception) {
            $verification->forceFill(['invalidated_at' => now()])->save();

            throw $exception;
        }

        Log::info('병의원 계정 생성 인증번호 발송 요청', [
            'invitation_id' => $verification->hospital_account_invitation_id,
            'phone_verification_id' => $verification->getKey(),
        ]);

        return [
            'verification_id' => (int) $verification->getKey(),
            'phone' => $this->maskPhone($phone),
            'code_expires_at' => $verification->code_expires_at?->toISOString(),
            'resend_after_seconds' => $resendSeconds,
        ];
    }

    private function queueSms(
        HospitalAccountPhoneVerification $verification,
        string $code,
        int $codeTtlMinutes,
    ): void {
        $invitation = $verification->invitation()->with(['hospital:id,name', 'hospitalEntry:id,hospital_name'])->firstOrFail();
        $hospitalName = $invitation->source_type === HospitalAccountInvitation::SOURCE_HOSPITAL
            ? $invitation->hospital?->name
            : $invitation->hospitalEntry?->hospital_name;
        $message = "[뷰랩] 병의원 계정 생성 인증번호는 [{$code}]입니다. {$codeTtlMinutes}분 내 입력해 주세요.";
        $phoneNormalized = AccountHospitalPhone::normalize((string) $verification->phone);

        $result = $this->smsBatchCreateAction->execute(
            purpose: 'hospital_account_phone_verification',
            idempotencyKey: (string) Str::uuid(),
            messageTemplate: '[뷰랩] 병의원 계정 생성 인증번호는 [{인증번호}]입니다.',
            metadata: ['phone_verification_id' => (int) $verification->getKey()],
            targetCount: 1,
            actor: null,
            resolveDeliveries: fn (): array => [
                new SmsDeliveryData(
                    deduplicationKey: "phone-verification:{$verification->getKey()}",
                    referenceType: $verification->getMorphClass(),
                    referenceId: (int) $verification->getKey(),
                    referenceLabel: (string) $hospitalName,
                    recipientType: null,
                    recipientId: null,
                    recipientKinds: ['PHONE_VERIFICATION'],
                    phone: (string) $verification->phone,
                    phoneNormalized: $phoneNormalized,
                    messageType: SmsMessage::type($message),
                    messageBody: $message,
                    byteLength: SmsMessage::byteLength($message),
                    status: SmsDelivery::STATUS_PENDING,
                ),
            ],
        );

        $deliveryId = $result->batch->deliveries()->value('id');
        $verification->forceFill([
            'sms_delivery_id' => $deliveryId !== null ? (int) $deliveryId : null,
        ])->save();
    }

    private function maskPhone(string $phone): string
    {
        return preg_replace('/(01\d)-?\d{3,4}-?(\d{4})/', '$1-****-$2', $phone) ?? $phone;
    }
}
