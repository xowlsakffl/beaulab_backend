<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Common\Authorization\AccessRoles;
use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Models\HospitalAccountPhoneVerification;
use App\Domains\AccountHospital\Queries\Hospital\HospitalAccountInvitationForHospitalQuery;
use App\Domains\AccountHospital\Support\AccountHospitalPhone;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationGuard;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationToken;
use App\Domains\AccountHospital\Support\HospitalAccountPhoneVerificationToken;
use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalBusinessRegistration;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use App\Domains\HospitalWallet\Queries\HospitalWalletCreateQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class HospitalAccountInvitationCompleteForHospitalAction
{
    public function __construct(
        private readonly HospitalAccountInvitationForHospitalQuery $query,
        private readonly HospitalWalletCreateQuery $walletCreateQuery,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @param  array{nickname:string,password:string,phone_verification_token:string}  $payload
     * @return array{account_hospital_id:int,hospital_id:int,message:string}
     */
    public function execute(string $invitationToken, array $payload): array
    {
        $nickname = trim((string) $payload['nickname']);

        $result = DB::transaction(function () use ($invitationToken, $payload, $nickname): array {
            $invitationTokenHash = HospitalAccountInvitationToken::hash($invitationToken);
            $candidate = HospitalAccountInvitationGuard::assertActive(
                $this->query->findByTokenHash($invitationTokenHash)
            );
            $source = $this->lockSource($candidate);

            $invitation = HospitalAccountInvitationGuard::assertActive(
                $this->query->findByTokenHash(
                    $invitationTokenHash,
                    lockForUpdate: true,
                )
            );

            $verification = $this->verifiedPhone(
                $invitation,
                (string) $payload['phone_verification_token'],
            );

            if ($this->query->nicknameExists($nickname)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 사용 중인 아이디입니다.');
            }

            $hospital = $source instanceof Hospital
                ? $this->existingHospital($invitation, $source)
                : $this->convertHospitalEntry($invitation, $verification->phone, $source);

            $verifiedPhone = AccountHospitalPhone::format((string) $verification->phone);
            $this->query->updateReceptionPhone($hospital, $verifiedPhone);

            $accountHospital = $this->query->createAccountHospital([
                'hospital_id' => $hospital->getKey(),
                'name' => (string) $hospital->name,
                'nickname' => $nickname,
                'phone' => $verifiedPhone,
                'phone_verified_at' => $verification->verified_at,
                'password' => (string) $payload['password'],
                'status' => AccountHospital::STATUS_ACTIVE,
            ]);
            $accountHospital->syncRoles([AccessRoles::HOSPITAL_OWNER]);

            $this->query->consumePhoneVerification($verification);
            $this->query->completeInvitation($invitation, $accountHospital);

            if ($source instanceof HospitalEntry) {
                $this->recordConvertedHospital($hospital);
            }

            return [
                'account_hospital_id' => (int) $accountHospital->getKey(),
                'hospital_id' => (int) $hospital->getKey(),
                'message' => '병의원 계정이 생성되었습니다.',
            ];
        }, 3);

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL);
        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_ENTRY);

        Log::info('병의원 계정 초대 가입 완료', [
            'account_hospital_id' => $result['account_hospital_id'],
            'hospital_id' => $result['hospital_id'],
        ]);

        return $result;
    }

    private function verifiedPhone(
        HospitalAccountInvitation $invitation,
        string $phoneVerificationToken,
    ): HospitalAccountPhoneVerification {
        $verification = $this->query->lockPhoneVerification(
            $invitation,
            HospitalAccountPhoneVerificationToken::hash($phoneVerificationToken),
        );

        if ($verification === null || ! $verification->isVerificationUsable()) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '휴대폰 인증이 유효하지 않거나 만료되었습니다. 다시 인증해 주세요.',
            );
        }

        return $verification;
    }

    private function lockSource(HospitalAccountInvitation $invitation): Hospital|HospitalEntry
    {
        return match ($invitation->source_type) {
            HospitalAccountInvitation::SOURCE_HOSPITAL => $invitation->hospital_id !== null
                ? $this->query->lockHospital((int) $invitation->hospital_id)
                : throw new CustomException(ErrorCode::INVALID_REQUEST, '초대 대상 병의원 정보가 없습니다.'),
            HospitalAccountInvitation::SOURCE_HOSPITAL_ENTRY => $invitation->hospital_entry_id !== null
                ? $this->query->lockHospitalEntry((int) $invitation->hospital_entry_id)
                : throw new CustomException(ErrorCode::INVALID_REQUEST, '초대 대상 입점신청 정보가 없습니다.'),
        };
    }

    private function existingHospital(
        HospitalAccountInvitation $invitation,
        Hospital $hospital,
    ): Hospital {
        if ((int) $invitation->hospital_id !== (int) $hospital->getKey()) {
            throw new CustomException(ErrorCode::TOKEN_ERROR, '초대 대상 병의원 정보가 일치하지 않습니다.');
        }

        if ($hospital->accountHospital !== null) {
            throw new CustomException(ErrorCode::TOKEN_ERROR, '계정 생성 링크가 유효하지 않거나 만료되었습니다.');
        }

        return $hospital;
    }

    private function convertHospitalEntry(
        HospitalAccountInvitation $invitation,
        string $verifiedPhone,
        HospitalEntry $entry,
    ): Hospital {
        if ((int) $invitation->hospital_entry_id !== (int) $entry->getKey()) {
            throw new CustomException(ErrorCode::TOKEN_ERROR, '초대 대상 입점신청 정보가 일치하지 않습니다.');
        }

        $this->assertConvertibleEntry($entry);

        $hospital = $this->query->createHospital([
            'name' => $entry->hospital_name,
            'department' => Hospital::DEPARTMENT_OTHER,
            'address' => $entry->address,
            'address_detail' => $entry->address_detail,
            'tel' => $entry->hospital_phone,
            'ad_reception_phone_1' => AccountHospitalPhone::format($verifiedPhone),
            'allow_status' => Hospital::ALLOW_NOT_APPLIED,
            'status' => Hospital::STATUS_ACTIVE,
        ]);

        $businessRegistration = $this->query->createBusinessRegistration([
            'hospital_id' => $hospital->getKey(),
            'business_number' => $entry->business_number,
            'company_name' => $entry->hospital_name,
            'ceo_name' => $entry->ceo_name,
            'business_address' => $entry->address,
            'business_address_detail' => $entry->address_detail,
            'status' => HospitalBusinessRegistration::STATUS_ACTIVE,
        ]);

        $this->query->transferBusinessRegistrationMedia($entry, $businessRegistration);
        $this->walletCreateQuery->createForHospital($hospital);
        $this->query->completeHospitalEntry($entry, $hospital);

        return $hospital;
    }

    private function assertConvertibleEntry(HospitalEntry $entry): void
    {
        if ($entry->allow_status !== HospitalEntry::ALLOW_APPROVED) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '승인된 입점신청만 계정을 생성할 수 있습니다.');
        }

        if ($entry->hospital_id !== null || $entry->converted_at !== null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 병의원으로 전환된 입점신청입니다.');
        }

        if ($entry->businessRegistrationFile === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '사업자등록증이 등록되어 있지 않습니다.');
        }

        if ($this->query->hospitalNameExists((string) $entry->hospital_name)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '동일한 병의원명이 이미 등록되어 있습니다.');
        }

        if ($this->query->businessNumberExists((string) $entry->business_number)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '동일한 사업자등록번호가 이미 등록되어 있습니다.');
        }
    }

    private function recordConvertedHospital(Hospital $hospital): void
    {
        $metadata = ['source' => 'hospital.account-invitation.complete'];

        $this->historyCreateAction->execute(
            target: $hospital,
            action: OperationHistory::ACTION_CREATED,
            metadata: $metadata,
            actorKind: OperationHistory::ACTOR_KIND_SYSTEM,
        );
    }
}
