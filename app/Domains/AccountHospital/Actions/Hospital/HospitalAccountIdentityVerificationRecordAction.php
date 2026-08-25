<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\HospitalAccountIdentityVerification;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Queries\Hospital\HospitalAccountIdentityVerificationQuery;
use App\Domains\AccountHospital\Support\AccountHospitalPhone;
use App\Domains\AccountHospital\Support\HospitalAccountIdentityVerificationToken;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 본인확인 제공자 콜백이 검증을 마친 결과를 일회용 가입 증표로 교환한다.
 * HTTP 요청이 이름과 전화번호를 직접 전달해 호출하는 용도로 사용하지 않는다.
 */
final class HospitalAccountIdentityVerificationRecordAction
{
    public function __construct(
        private readonly HospitalAccountIdentityVerificationQuery $query,
    ) {}

    /**
     * @param  array{provider:string,provider_reference:string,verified_name:string,verified_phone:string,ci?:string|null}  $result
     * @return array{identity_verification_token:string, expires_at:?string}
     */
    public function execute(HospitalAccountInvitation $invitation, array $result): array
    {
        $provider = mb_strtoupper(trim((string) $result['provider']));
        $providerReference = trim((string) $result['provider_reference']);
        $verifiedName = trim((string) $result['verified_name']);
        $verifiedPhone = AccountHospitalPhone::format((string) $result['verified_phone']);
        $ci = trim((string) ($result['ci'] ?? ''));

        if (
            $provider === ''
            || mb_strlen($provider) > 30
            || $providerReference === ''
            || $verifiedName === ''
            || mb_strlen($verifiedName) > 100
            || ! AccountHospitalPhone::isValid($verifiedPhone)
        ) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '본인확인 결과가 올바르지 않습니다.');
        }

        $rawToken = HospitalAccountIdentityVerificationToken::make();
        $providerReferenceHash = hash('sha256', $providerReference);
        $expiresAt = now()->addMinutes(
            (int) config('hospital_account_invitation.identity_verification_ttl_minutes', 15)
        );

        $verification = DB::transaction(function () use (
            $invitation,
            $provider,
            $providerReferenceHash,
            $verifiedName,
            $verifiedPhone,
            $ci,
            $rawToken,
            $expiresAt,
        ): HospitalAccountIdentityVerification {
            $lockedInvitation = HospitalAccountInvitationGuard::assertActive(
                $this->query->lockInvitation((int) $invitation->getKey())
            );

            $existing = $this->query->lockByProviderReference($provider, $providerReferenceHash);
            if ($existing !== null && $existing->consumed_at !== null) {
                throw new CustomException(ErrorCode::TOKEN_ERROR, '이미 사용된 본인확인 결과입니다.');
            }

            $attributes = [
                'hospital_account_invitation_id' => $lockedInvitation->getKey(),
                'token_hash' => HospitalAccountIdentityVerificationToken::hash($rawToken),
                'verified_name' => $verifiedName,
                'verified_phone' => $verifiedPhone,
                'ci_hash' => $ci !== '' ? hash('sha256', $ci) : null,
                'verified_at' => now(),
                'expires_at' => $expiresAt,
                'consumed_at' => null,
            ];

            if ($existing !== null) {
                if ((int) $existing->hospital_account_invitation_id !== (int) $lockedInvitation->getKey()) {
                    throw new CustomException(ErrorCode::TOKEN_ERROR, '본인확인 결과가 현재 초대와 일치하지 않습니다.');
                }

                return $this->query->refreshProof($existing, $attributes);
            }

            return $this->query->create([
                ...$attributes,
                'provider' => $provider,
                'provider_reference_hash' => $providerReferenceHash,
            ]);
        });

        Log::info('병의원 계정 생성 본인확인 완료', [
            'invitation_id' => $invitation->getKey(),
            'identity_verification_id' => $verification->getKey(),
            'provider' => $provider,
        ]);

        return [
            'identity_verification_token' => $rawToken,
            'expires_at' => $verification->expires_at?->toISOString(),
        ];
    }
}
