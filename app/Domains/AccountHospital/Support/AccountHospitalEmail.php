<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;

final class AccountHospitalEmail
{
    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function isValid(string $email): bool
    {
        return strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function mask(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'***@'.$domain;
    }

    public static function assertAvailable(string $email, ?int $accountId = null): void
    {
        if (! self::isValid($email)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이메일 주소를 정확히 입력해 주세요.');
        }
        if (AccountHospital::withTrashed()->where('email', self::normalize($email))
            ->when($accountId !== null, fn ($query) => $query->where('id', '!=', $accountId))->exists()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 사용 중인 이메일입니다.');
        }
    }
}
