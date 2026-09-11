<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Models\HospitalAccountPasswordReset;
use App\Domains\AccountHospital\Queries\HospitalAccountPasswordResetQuery;

final class HospitalAccountPasswordResetGuard
{
    public function __construct(private readonly HospitalAccountPasswordResetQuery $query) {}

    /** @return array{AccountHospital, HospitalAccountPasswordReset} */
    public function lockValid(string $token): array
    {
        if (! preg_match('/^[A-Za-z0-9]{64}$/D', $token)) {
            throw self::invalidToken();
        }

        $hash = HospitalAccountPasswordResetToken::hash($token);
        $candidate = $this->query->findByHash($hash);
        if ($candidate === null) {
            throw self::invalidToken();
        }

        // 발송/소비가 항상 계정 -> 링크 순서로 잠금을 획득한다.
        $account = $this->query->lockAccount($candidate->account_hospital_id);
        $reset = $this->query->findByHash($hash, true);
        if (! self::canReset($account) || $reset === null
            || $reset->used_at !== null || $reset->revoked_at !== null
            || $reset->expires_at === null || $reset->expires_at->lte(now())
            || ! hash_equals($reset->credential_hash, HospitalAccountPasswordResetToken::credentialHash($account))) {
            throw self::invalidToken();
        }

        return [$account, $reset];
    }

    public static function canReset(?AccountHospital $account): bool
    {
        return $account !== null && $account->isActive() && $account->hospital !== null
            && $account->verifiedEmail() !== null && AccountHospitalEmail::isValid($account->verifiedEmail());
    }

    private static function invalidToken(): CustomException
    {
        return new CustomException(ErrorCode::TOKEN_ERROR, '비밀번호 재설정 링크가 유효하지 않거나 만료되었습니다.');
    }
}
